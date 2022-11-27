<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

use Goomento\Core\Helper\HooksHelper;

class ScriptsManager extends AssetDependencies
{
    /**
     * Holds handles of scripts which are enqueued in footer.
     *
     * @var array
     */
    protected $inFooter = [];

    /**
     * Holds a string which contains the type attribute for script tag.
     *
     * If the current theme does not declare HTML5 support for 'script',
     * then it initializes as `type='text/javascript'`.
     *
     * @var string
     */
    private $typeAttr = '';

    /**
     * @var null
     */
    protected $requiredConfig;

    /**
     * @var array
     */
    private $defaultResource = [
        'jquery',
        'underscore',
        'jquery/ui'
    ];

    /**
     * @inheritDoc
     */
    protected function init()
    {
        foreach ($this->defaultResource as $lib) {
            $this->done[] = $lib;
            $this->add($lib, '');
        }
    }

    /**
     * Prints scripts.
     *
     * Prints the scripts passed to it or the print queue. Also prints all necessary dependencies.
     *
     *
     * @param mixed $handles Optional. Scripts to be printed. (void) prints queue, (string) prints
     *                       that script, (array of strings) prints those scripts. Default false.
     * @param int   $group   Optional. If scripts were queued in groups prints this group number.
     *                       Default false.
     * @return array Scripts that have been printed.
     */
    public function printScripts($handles = false, $group = false)
    {
        return $this->doItems($handles, $group);
    }

    /**
     * Prints extra scripts of a registered script.
     *
     *
     * @param string $handle The script's registered handle.
     */
    public function printExtraScript(string $handle) : void
    {
        $output = $this->getData($handle, 'data');
        if (!$output) {
            return;
        }

        $html = "<script{$this->typeAttr}>\n";

        // CDATA is not needed for HTML 5.
        if ($this->typeAttr) {
            $html .= "/* <![CDATA[ */\n";
        }

        $html = "$output\n";

        if ($this->typeAttr) {
            $html .= "/* ]]> */\n";
        }

        $html = "</script>\n";

        // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
        echo $html;
    }

    /**
     * Add handle to become dependency
     *
     * @param array|string $deps
     * @param string $handle
     * @return $this
     */
    public function addDeps($deps, string $handle)
    {
        if (isset($this->registered[ $handle ])) {
            $obj = $this->registered[ $handle ];
            $depends = (array) $obj['deps'];
            $depends = array_merge($depends, (array) $deps);
            $this->registered[ $handle ]['deps'] = array_unique($depends);
        }

        return $this;
    }

    /**
     * Processes a script dependency.
     *
     * @param string    $handle The script's registered handle.
     * @param int|false $group  Optional. Group level: (int) level, (false) no groups. Default false.
     * @return bool True on success, false on failure.
     */
    public function doItem($handle, $group = false): bool
    {
        if (! parent::doItem($handle)) {
            return false;
        }

        if (0 === $group && $this->groups[ $handle ] > 0) {
            $this->inFooter[] = $handle;
            return false;
        }

        if (false === $group && in_array($handle, $this->inFooter, true)) {
            $this->inFooter = array_diff($this->inFooter, (array) $handle);
        }

        $obj = $this->registered[ $handle ];

        $src         = $obj['src'];
        $print       = (bool) isset($obj['extra']['print']) && $obj['extra']['print'];
        $condBefore = '';
        $condAfter  = '';
        $conditional = $obj['extra']['conditional'] ?? '';

        if ($conditional) {
            $condBefore = "<!--[if {$conditional}]>\n";
            $condAfter  = "<![endif]-->\n";
        }

        $beforeHandle = $this->printInlineScript($handle, 'before', false);
        $afterHandle  = $this->printInlineScript($handle, 'after', false);

        if ($beforeHandle) {
            $beforeHandle = sprintf("<script%s>\n%s\n</script>\n", $this->typeAttr, $beforeHandle);
        }

        if ($afterHandle) {
            $afterHandle = sprintf("<script%s>\n%s\n</script>\n", $this->typeAttr, $afterHandle);
        }

        if ($beforeHandle || $afterHandle) {
            $inlineScriptTag = $condBefore . $beforeHandle . $afterHandle . $condAfter;
        } else {
            $inlineScriptTag = '';
        }

        $hasConditionalData = $conditional && $this->getData($handle, 'data');

        if ($hasConditionalData) {
            // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
            echo $condBefore;
        }

        $this->printExtraScript($handle);

        if ($hasConditionalData) {
            // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
            echo $condAfter;
        }

        // A single item may alias a set of items, by having dependencies, but no source.
        if (!$src) {
            if ($inlineScriptTag) {
                // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
                echo $inlineScriptTag;
            }

            return true;
        }

        $tag  = $condBefore . $beforeHandle;
        if ($print) {
            $requireJs = [];
            $depends = $obj['deps'];
            if (!empty($depends)) {
                $requireJs = $depends;
            }
            $requireJs[] = $handle;
            $requireJs = implode('\',\'', $requireJs);
            $tag .= sprintf("<script>require(['%s'])</script>", $requireJs);
        }

        $tag .= $afterHandle . $condAfter;

        /**
         * Filters the HTML script tag of an enqueued script.
         *
         * @param string $tag    The `<script>` tag for the enqueued script.
         * @param string $handle The script's registered handle.
         * @param string $src    The script's source URL.
         */
        $tag = HooksHelper::applyFilters('script_loader_tag', $tag, $handle, $src)->getResult();

        // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
        echo $tag;

        return true;
    }

    /**
     * Adds extra code to a registered script.
     *
     * @param string $handle   Name of the script to add the inline script to. Must be lowercase.
     * @param string $data     String containing the javascript to be added.
     * @param string $position Optional. Whether to add the inline script before the handle
     *                         or after. Default 'after'.
     * @return bool True on success, false on failure.
     */
    public function addInlineScript($handle, $data, $position = 'after')
    {
        if (! $data) {
            return false;
        }

        if ('after' !== $position) {
            $position = 'before';
        }

        $script   = (array) $this->getData($handle, $position);
        $script[] = $data;

        return $this->addData($handle, $position, $script);
    }

    /**
     * Prints inline scripts registered for a specific handle.
     *
     * @param string $handle   Name of the script to add the inline script to. Must be lowercase.
     * @param string $position Optional. Whether to add the inline script before the handle
     *                         or after. Default 'after'.
     * @param bool   $echo     Optional. Whether to echo the script instead of just returning it.
     *                         Default true.
     * @return string|false Script on success, false otherwise.
     */
    public function printInlineScript($handle, $position = 'after', $echo = true)
    {
        $output = $this->getData($handle, $position);

        if (empty($output)) {
            return false;
        }

        $output = trim(implode("\n", $output), "\n");

        if ($echo) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            printf("<script%s>\n%s\n</script>\n", $this->typeAttr, $output);
        }

        return $output;
    }

    /**
     * Sets handle group.
     *
     * @param string    $handle    Name of the item. Should be unique.
     * @param bool      $recursion Internal flag that calling function was called recursively.
     * @param int|false $group     Optional. Group level: (int) level, (false) no groups. Default false.
     * @return bool Not already in the group or a lower group
     *
     */
    public function setGroup($handle, $recursion, $group = false)
    {
        if (isset($this->registered[ $handle ]->args) && $this->registered[ $handle ]->args === 1) {
            $grp = 1;
        } else {
            $grp = (int) $this->getData($handle, 'group');
        }

        if (false !== $group && $grp > $group) {
            $grp = $group;
        }

        return parent::setGroup($handle, $recursion, $grp);
    }

    /**
     * Determines script dependencies.
     *
     * @param mixed     $handles   Item handle and argument (string) or item handles and arguments (array of strings).
     * @param bool      $recursion Internal flag that function is calling itself.
     * @param int|false $group     Optional. Group level: (int) level, (false) no groups. Default false.
     * @return bool True on success, false on failure.
     */
    public function allDeps($handles, $recursion = false, $group = false)
    {
        $r = parent::allDeps($handles, $recursion, $group);
        if (! $recursion) {
            $this->toDo = HooksHelper::applyFilters('print_scripts_array', $this->toDo)->getResult();
        }
        return $r;
    }

    /**
     * Processes items and dependencies for the head group.
     *
     * @return array Handles of items that have been processed.
     */
    public function doHeadItems(): array
    {
        $this->printRequireConfig();
        $this->doItems(false, 0);
        return $this->done;
    }

    /**
     * Processes items and dependencies for the footer group.
     *
     *
     * @return array Handles of items that have been processed.
     */
    public function doFooterItems()
    {
        $this->doItems(false, 1);
        return $this->done;
    }

    /**
     * Print require config
     */
    public function printRequireConfig()
    {
        if (is_null($this->requiredConfig)) {
            $this->requiredConfig = [];
            $config = ['paths' => [], 'shim' => []];
            foreach ($this->registered as $item) {
                if (empty($item['src'])) {
                    continue;
                }

                $this->requiredConfig[] = $item['handle'];
                $config['paths'][$item['handle']] = $item['src'];
                if (!empty($item['deps'])) {
                    $config['shim'][$item['handle']]['deps'] = array_values($item['deps']);
                }
                if (isset($item['args']['requirejs']) && !empty($item['args']['requirejs'])) {
                    $config = array_merge_recursive($config, $item['args']['requirejs']);
                }
            }
            $jsonVariable = json_encode($config);
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            printf("<script>(function(require){(function() {require.config(%s)})();})(require)</script>", $jsonVariable);
        }
    }
}
