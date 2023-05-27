<?php /** @noinspection ALL */
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Helper;

use Goomento\Core\Model\ScriptsManager;
use Goomento\Core\Model\StylesManager;

// phpcs:disable Magento2.Functions.StaticFunction.StaticFunction
class ThemeHelper
{
    /**
     * @var ScriptsManager
     */
    private static $scriptsManager;

    /**
     * @var StylesManager
     */
    private static $stylesManager;

    /**
     * @return ScriptsManager
     */
    public static function getScriptsManager()
    {
        if (self::$scriptsManager === null) {
            /** @var ScriptsManager $scriptManager */
            self::$scriptsManager = ObjectManagerHelper::get(
                ScriptsManager::class
            );
        }

        return self::$scriptsManager;
    }

    /**
     * @return StylesManager
     */
    public static function getStylesManager()
    {
        if (self::$stylesManager === null) {
            /** @var StylesManager $stylesManager */
            self::$stylesManager = ObjectManagerHelper::get(
                StylesManager::class
            );
        }

        return self::$stylesManager;
    }

    /**
     * Register a CSS stylesheet.
     * @param string $handle Name of the stylesheet. Should be unique.
     * @param string|bool      $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
     *                                 If source is set to false, stylesheet is an alias of other stylesheets it depends on.
     * @param string[]         $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
     * @param string|bool|null $ver    Optional. String specifying stylesheet version number, if it has one, which is added to the URL
     *                                 as a query string for cache busting purposes. If version is set to false, a version
     *                                 number is automatically added equal to current installed WordPress version.
     *                                 If set to null, no version is added.
     * @param string           $media  Optional. The media for which this stylesheet has been defined.
     *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
     *                                 '(orientation: portrait)' and '(max-width: 640px)'.
     * @return bool Whether the style has been registered. True on success, false on failure.
     */
    public static function registerStyle(
        string $handle,
        $src,
        array $deps = [],
        $ver = false,
        string $media = 'all'
    ) {
        return self::getStylesManager()->add($handle, $src, $deps, $ver, $media);
    }

    /**
     * Register a new script.
     *
     * @param string $handle    Name of the script. Should be unique.
     * @param string|bool      $src       Full URL of the script, or path of the script relative to the WordPress root directory.
     *                                    If source is set to false, script is an alias of other scripts it depends on.
     * @param string[]         $deps      Optional. An array of registered script handles this script depends on. Default empty array.
     *                                    as a query string for cache busting purposes. If version is set to false, a version
     *                                    number is automatically added equal to current installed WordPress version.
     *                                    If set to null, no version is added.
     * @return bool Whether the script has been registered. True on success, false on failure.
     */
    public static function registerScript(
        string $handle,
        string $src,
        array $deps = [],
        array $args = []
    ) {
        self::getScriptsManager()->add($handle, $src, $deps, false, $args);
        return true;
    }

    /**
     * Un-register scripts from manager
     *
     * @param string|array $handle
     * @return void
     */
    public static function removeScripts($handle)
    {
        self::getScriptsManager()->remove($handle);
    }

    /**
     * Un-register styles from manager
     *
     * @param string|array $handle
     * @return void
     */
    public static function removeStyle($handle)
    {
        self::getStylesManager()->remove($handle);
    }

    /**
     * Enqueue a CSS stylesheet.
     *
     * Registers the style if source provided (does NOT overwrite) and enqueues.
     * @param string $handle Name of the stylesheet. Should be unique.
     */
    public static function enqueueStyle(string $handle)
    {
        self::getStylesManager()->enqueue($handle);
    }

    /**
     * Enqueue a script.
     *
     * Registers the script if $src provided (does NOT overwrite), and enqueues it.
     * @param string $handle Name of the script. Should be unique.
     */
    public static function enqueueScript(string $handle)
    {

        self::getScriptsManager()->addData($handle, 'print', 1);

        self::getScriptsManager()->enqueue($handle);
    }

    /**
     * Adds extra code to a registered script.
     *
     * Code will only be added if the script is already in the queue.
     * Accepts a string $data containing the Code. If two or more code blocks
     * are added to the same script $handle, they will be printed in the order
     * they were added, i.e. the latter added code can redeclare the previous.
     * @param string $handle   Name of the script to add the inline script to.
     * @param string $data     String containing the javascript to be added.
     * @param string $position Optional. Whether to add the inline script before the handle
     *                         or after. Default 'after'.
     * @return bool True on success, false on failure.
     */
    public static function inlineScript(string $handle, string $data, string $position = 'after')
    {
        return self::getScriptsManager()->addInlineScript($handle, $data, $position);
    }

    /**
     * Check whether a CSS stylesheet has been added to the queue.
     * @param string $handle Name of the stylesheet.
     * @param string $list   Optional. Status of the stylesheet to check. Default 'enqueued'.
     *                       Accepts 'enqueued', 'registered', 'queue', 'to_do', and 'done'.
     * @return bool Whether style is queued.
     */
    public static function styleIs(string $handle, string $list = 'registered')
    {
        return self::getStylesManager()->query($handle, $list);
    }

    /**
     * Add extra CSS styles to a registered stylesheet.
     *
     * Styles will only be added if the stylesheet is already in the queue.
     * Accepts a string $data containing the CSS. If two or more CSS code blocks
     * are added to the same stylesheet $handle, they will be printed in the order
     * they were added, i.e. the latter added styles can redeclare the previous.
     *
     * @param string $handle Name of the stylesheet to add the extra styles to.
     * @param string $code   String containing the CSS styles to be added.
     * @return bool True on success, false on failure.
     */
    public static function inlineStyle(string $handle, string $code): bool
    {
        return self::getStylesManager()->addInlineStyle($handle, $code);
    }

    /**
     * Add dependencies to the target handle
     *
     * @param string|array $deps
     * @param string $handle
     * @return ScriptsManager
     */
    public static function addScriptDeps($deps, string $handle)
    {
        return self::getScriptsManager()->addDeps($deps, $handle);
    }
}
