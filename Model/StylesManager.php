<?php /** @noinspection ALL */
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

use Goomento\Core\Helper\HooksHelper;

// phpcs:disable Magento2.Security.LanguageConstruct.DirectOutput
// phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
class StylesManager extends AssetDependencies
{
    /**
     * The current text direction.
     *
     * @var string
     */
    private $textDirection = 'ltr';

    /**
     *
     * @var string
     */
    private $typeAttr = ' type="text/css"';

    /**
     * Processes a style dependency.
     *
     * @param string $handle The style's registered handle.
     * @return bool True on success, false on failure.
     */
    public function doItem(string $handle, $group = false) : bool
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
        $condBefore = '';
        $condAfter  = '';
        $conditional = $obj['extra']['conditional'] ?? '';

        if ($conditional) {
            $condBefore = "<!--[if {$conditional}]>\n";
            $condAfter  = "<![endif]-->\n";
        }

        $inlineStyle = $this->printInlineStyle($handle, false);

        if ($inlineStyle) {
            $inlineStyleTag = sprintf(
                "<style id='%s-inline-css'%s>\n%s\n</style>\n",
                $handle,
                $this->typeAttr,
                $inlineStyle
            );
        } else {
            $inlineStyleTag = '';
        }

        if (isset($obj['args'])) {
            $media = $obj['args'];
        } else {
            $media = 'all';
        }

        // A single item may alias a set of items, by having dependencies, but no source.
        if (! $src) {
            if ($inlineStyleTag) {
                echo $inlineStyleTag;
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
        $tag = HooksHelper::applyFilters('style_loader_tag', $tag, $handle, $href, $media)->getResult();

        if ('rtl' === $this->textDirection && isset($obj->extra['rtl']) && $obj->extra['rtl']) {
            if (is_bool($obj->extra['rtl']) || 'replace' === $obj->extra['rtl']) {
                $suffix   = $obj->extra['suffix'] ?? '';
                $rtlHref = str_replace("{$suffix}.css", "-rtl{$suffix}.css", $this->_cssHref($src, $ver, "$handle-rtl"));
            } else {
                $rtlHref = $this->_cssHref($obj->extra['rtl'], $ver, "$handle-rtl");
            }

            $rtlTag = sprintf(
                "<link rel='%s' id='%s-rtl-css' %s href='%s'%s media='%s' />\n",
                $rel,
                $handle,
                $title,
                $rtlHref,
                $this->typeAttr,
                $media
            );

            $rtlTag = HooksHelper::applyFilters('style_loader_tag', $rtlTag, $handle, $rtlHref, $media)->getResult();

            if ($obj->extra['rtl'] === 'replace') {
                $tag = $rtlTag;
            } else {
                $tag .= $rtlTag;
            }
        }

        echo $condBefore;
        echo $tag;
        $this->printInlineStyle($handle);
        echo $condAfter;

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
    public function allDeps($handles, $recursion = false, $group = false) : bool
    {
        $r = parent::allDeps($handles, $recursion, $group);
        if (! $recursion) {
            /**
             * Filters the array of enqueued styles before processing for output.
             *
             * @param string[] $toDo The list of enqueued style handles about to be processed.
             */
            $this->toDo = HooksHelper::applyFilters('print_styles_array', $this->toDo)->getResult();
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
        return HooksHelper::applyFilters('style_loader_src', $src, $handle)->getResult();
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
