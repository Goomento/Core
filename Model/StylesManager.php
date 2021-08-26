<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

/**
 * Class StylesManager
 * @package Goomento\PageBuilder\Model
 */
class StylesManager extends AssetDependencies
{
    /**
     * Base URL for styles.
     *
     * Full URL with trailing slash.
     *
     * @var string
     */
    private $baseUrl;

    /**
     * The current text direction.
     *
     * @var string
     */
    private $textDirection = 'ltr';

    /**
     * List of default directories.
     *
     * @var array
     */
    private $defaultDirs;

    /**
     * Holds a string which contains the type attribute for style tag.
     *
     * If the current theme does not declare HTML5 support for 'style',
     * then it initializes as `type='text/css'`.
     *
     * @var string
     */
    private $typeAttr = '';

    /**
     * Processes a style dependency.
     *
     * @param string $handle The style's registered handle.
     * @return bool True on success, false on failure.
     */
    public function doItem($handle, $group = false)
    {
        if (! parent::doItem($handle)) {
            return false;
        }

        $obj = $this->registered[ $handle ];

        if (!$obj['ver']) {
            $ver = '';
        } else {
            $ver = $obj['ver'];
        }

        if (isset($this->args[ $handle ])) {
            $ver = $ver ? $ver . '&amp;' . $this->args[ $handle ] : $this->args[ $handle ];
        }

        $src         = $obj['src'];
        $cond_before = '';
        $cond_after  = '';
        $conditional = $obj['extra']['conditional'] ?? '';

        if ($conditional) {
            $cond_before = "<!--[if {$conditional}]>\n";
            $cond_after  = "<![endif]-->\n";
        }

        $inline_style = $this->printInlineStyle($handle, false);

        if ($inline_style) {
            $inline_style_tag = sprintf(
                "<style id='%s-inline-css'%s>\n%s\n</style>\n",
                $handle,
                $this->typeAttr,
                $inline_style
            );
        } else {
            $inline_style_tag = '';
        }

        if (isset($obj['args'])) {
            $media = $obj['args'];
        } else {
            $media = 'all';
        }

        // A single item may alias a set of items, by having dependencies, but no source.
        if (! $src) {
            if ($inline_style_tag) {
                echo $inline_style_tag;
            }

            return true;
        }

        $href = $this->_cssHref($src, $ver, $handle);
        if (! $href) {
            return true;
        }

        $rel   = isset($obj['extra']['alt']) && $obj['extra']['alt'] ? 'alternate stylesheet' : 'stylesheet';
        $title = isset($obj['extra']['title']) ? sprintf("title='%s'", $obj['extra']['title']) : '';

        $tag = sprintf(
            "<link rel=\"%s\" id=\"%s-css\" %s href=\"%s\"%s media=\"%s\" />\n",
            $rel,
            $handle,
            $title,
            $href,
            $this->typeAttr,
            $media
        );

        /**
         * Filters the HTML link tag of an enqueued style.
         *
         * @param string $html   The link tag for the enqueued style.
         * @param string $handle The style's registered handle.
         * @param string $href   The stylesheet's source URL.
         * @param string $media  The stylesheet's media attribute.
         */
        $tag = $this->hookManager->applyFilters('style_loader_tag', $tag, $handle, $href, $media);

        if ('rtl' === $this->textDirection && isset($obj->extra['rtl']) && $obj->extra['rtl']) {
            if (is_bool($obj->extra['rtl']) || 'replace' === $obj->extra['rtl']) {
                $suffix   = $obj->extra['suffix'] ?? '';
                $rtl_href = str_replace("{$suffix}.css", "-rtl{$suffix}.css", $this->_cssHref($src, $ver, "$handle-rtl"));
            } else {
                $rtl_href = $this->_cssHref($obj->extra['rtl'], $ver, "$handle-rtl");
            }

            $rtl_tag = sprintf(
                "<link rel='%s' id='%s-rtl-css' %s href='%s'%s media='%s' />\n",
                $rel,
                $handle,
                $title,
                $rtl_href,
                $this->typeAttr,
                $media
            );

            $rtl_tag = $this->hookManager->applyFilters('style_loader_tag', $rtl_tag, $handle, $rtl_href, $media);

            if ($obj->extra['rtl'] === 'replace') {
                $tag = $rtl_tag;
            } else {
                $tag .= $rtl_tag;
            }
        }

        echo $cond_before;
        echo $tag;
        $this->printInlineStyle($handle);
        echo $cond_after;

        return true;
    }

    /**
     * Adds extra CSS styles to a registered stylesheet.
     *
     * @param string $handle The style's registered handle.
     * @param string $code   String containing the CSS styles to be added.
     * @return bool True on success, false on failure.
     */
    public function addInlineStyle($handle, $code)
    {
        if (! $code) {
            return false;
        }

        $after = $this->getData($handle, 'after');
        if (! $after) {
            $after = [];
        }

        $after[] = $code;

        return $this->addData($handle, 'after', $after);
    }

    /**
     * Prints extra CSS styles of a registered stylesheet.
     *
     *
     * @param string $handle The style's registered handle.
     * @param bool   $echo   Optional. Whether to echo the inline style instead of just returning it.
     *                       Default true.
     * @return string|bool False if no data exists, inline styles if `$echo` is true, true otherwise.
     */
    public function printInlineStyle($handle, $echo = true)
    {
        $output = $this->getData($handle, 'after');

        if (empty($output)) {
            return false;
        }

        $output = implode("\n", $output);

        if (! $echo) {
            return $output;
        }

        printf(
            "<style id='%s-inline-css'%s>\n%s\n</style>\n",
            $handle,
            $this->typeAttr,
            $output
        );

        return true;
    }

    /**
     * Determines style dependencies.
     *
     * @param mixed     $handles   Item handle and argument (string) or item handles and arguments (array of strings).
     * @param bool      $recursion Internal flag that function is calling itself.
     * @param int|false $group     Group level: (int) level, (false) no groups.
     * @return bool True on success, false on failure.
     */
    public function allDeps($handles, $recursion = false, $group = false)
    {
        $r = parent::allDeps($handles, $recursion, $group);
        if (! $recursion) {
            /**
             * Filters the array of enqueued styles before processing for output.
             *
             * @param string[] $to_do The list of enqueued style handles about to be processed.
             */
            $this->toDo = $this->hookManager->applyFilters('print_styles_array', $this->toDo);
        }
        return $r;
    }

    /**
     * @param $src
     * @param $ver
     * @param $handle
     * @return mixed
     */
    public function _cssHref($src, $ver, $handle)
    {
        if (! is_bool($src) && ! preg_match('|^(https?:)?//|', $src)) {
            $src = $this->baseUrl . $src;
        }

        if ($ver) {
            $src = $src . '?v=' . $ver;
        }
        /**
         * Filters an enqueued style's fully-qualified URL.
         *
         *
         * @param string $src    The source URL of the enqueued style.
         * @param string $handle The style's registered handle.
         */
        return $this->hookManager->applyFilters('style_loader_src', $src, $handle);
    }

    /**
     * Whether a handle's source is in a default directory.
     *
     * @param string $src The source of the enqueued style.
     * @return bool True if found, false if not.
     */
    public function inDefaultDir($src)
    {
        if (! $this->defaultDirs) {
            return true;
        }

        foreach ((array) $this->defaultDirs as $test) {
            if (0 === strpos($src, $test)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Processes items and dependencies for the footer group.
     *
     * HTML 5 allows styles in the body, grab late enqueued items and output them in the footer.
     *
     * @return array Handles of items that have been processed.
     */
    public function doFooterTtems()
    {
        $this->doItems(false, 1);
        return $this->done;
    }

    /**
     * Query list for an item.
     *
     * @param string $handle Name of the item. Should be unique.
     * @param string $list Property name of list array.
     * @return array|bool
     */
    public function query($handle, $list = 'registered')
    {
        switch ($list) {
            case 'registered':
            case 'scripts': // back compat
                if (isset($this->registered[ $handle ])) {
                    return $this->registered[ $handle ];
                }
                return false;

            case 'enqueued':
            case 'queue':
                if (in_array($handle, $this->queue)) {
                    return true;
                }
                return $this->recurseDeps((array) $this->queue, $handle);

            case 'to_do':
            case 'to_print': // back compat
                return in_array($handle, $this->toDo);

            case 'done':
            case 'printed': // back compat
                return in_array($handle, $this->done);
        }
        return false;
    }
}
