<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Helper;


use Goomento\Core\Traits\TraitStaticInstances;
use Goomento\Core\Model\HookManager;

/**
 * Class Hooks
 * @package Goomento\Core\Helper
 */
class Hooks
{
    use TraitStaticInstances;

    /**
     * Execute functions hooked on a specific action hook.
     *
     * This function invokes all functions attached to action hook `$tag`. It is
     * possible to create new action hooks by simply calling this function,
     * specifying the name of the new hook using the `$tag` parameter.
     * @param string $tag The name of the action to hook the $callbackToAdd callback to.
     * @param callable|string|array $callbackToAdd The callback to be run when the action is applied.
     * @param int $sortOrder The order in which the functions associated with a
     *                                  particular action are executed. Lower numbers correspond with
     *                                  earlier execution, and functions with the same priority are executed
     *                                  in the order in which they were added to the action.
     *
     */
    public static function addAction(string $tag, $callbackToAdd, int $sortOrder = 10)
    {
        /** @var HookManager $instance */
        $instance = self::getInstance(HookManager::class);
        $instance->addAction($tag, $callbackToAdd, $sortOrder);
    }

    /**
     * Hooks a function or method to a specific filter action.
     *
     * @param string $tag The name of the filter to hook the $function_to_add callback to.
     * @param callable|string|array $callbackToAdd The callback to be run when the filter is applied.
     * @param int $sortOrder The order in which the functions associated with a
     *                                  particular action are executed. Lower numbers correspond with
     *                                  earlier execution, and functions with the same priority are executed
     *                                  in the order in which they were added to the action.
     *
     */
    public static function addFilter(
        string $tag,
        $callbackToAdd,
        int $sortOrder = 10
    )
    {
        /** @var HookManager $instance */
        $instance = self::getInstance(HookManager::class);
        $instance->addFilter($tag, $callbackToAdd, $sortOrder);
    }

    /**
     * Calls the callback functions that have been added to a filter hook.
     *
     * The callback functions attached to the filter hook are invoked by calling
     * this function. This function can be used to create a new filter hook by
     * simply calling this function with the name of the new hook specified using
     * the `$tag` parameter.
     *
     * The function also allows for multiple additional arguments to be passed to hooks.
     * @param string $tag     The name of the filter hook.
     * @param mixed  $value   The value to filter.
     */
    public static function applyFilters(string $tag, ...$value)
    {
        /** @var HookManager $instance */
        $instance = self::getInstance(HookManager::class);
        return $instance->applyFilters($tag, ...$value);
    }

    /**
     * Execute functions hooked on a specific action hook.
     *
     * This function invokes all functions attached to action hook `$tag`. It is
     * possible to create new action hooks by simply calling this function,
     * specifying the name of the new hook using the `$tag` parameter.
     * @param string $tag    The name of the action to be executed.
     * @param mixed  ...$arg Optional. Additional arguments which are passed on to the
     *                       functions hooked to the action. Default empty.
     */
    public static function doAction(string $tag, ...$arg)
    {
        /** @var HookManager $instance */
        $instance = self::getInstance(HookManager::class);
        $instance->doAction($tag, ...$arg);
    }

    /**
     * Retrieve the number of times an action is fired
     *
     * @param string $tag The filter hook to which the function to be checked
     * @return int|mixed
     */
    public static function didAction(string $tag)
    {
        /** @var HookManager $instance */
        $instance = self::getInstance(HookManager::class);
        return $instance->didAction($tag);
    }

    /**
     * Unhooks a function or method from a specific filter action.
     *
     * @param string $tag The filter hook to which the function to be removed is hooked. Used
     *                                     for building the callback ID when SPL is not available.
     * @param callable|string|array|null $callbackToRemove Optional. The callback to be removed from running when the filter is applied.
     * @param int $sortOrder The exact priority used when adding the original filter callback.
     * @return bool Whether the callback existed before it was removed.
     */
    public static function removeFilter(string $tag, $callbackToRemove = null, int $sortOrder = 10)
    {
        /** @var HookManager $instance */
        $instance = self::getInstance(HookManager::class);
        return $instance->removeFilter($tag, $callbackToRemove, $sortOrder);
    }

    /**
     * Unhooks a function or method from a specific filter action.
     *
     * @param string $tag The filter hook to which the function to be removed is hooked. Used
     *                                     for building the callback ID when SPL is not available.
     * @param callable|string|array $callbackToRemove The callback to be removed from running when the filter is applied.
     * @param int $sortOrder The exact priority used when adding the original filter callback.
     * @return bool Whether the callback existed before it was removed.
     */
    public static function removeAction(string $tag, $callbackToRemove, int $sortOrder = 10)
    {
        return self::removeFilter($tag, $callbackToRemove, $sortOrder);
    }

    /**
     * Checks if a specific action has been registered for this hook.
     *
     * @param string $tag The name of the filter hook. Used for building
     *                                         the callback ID when SPL is not available. Default empty.
     * @param callable|null $functionToCheck Optional. The callback to check for. Default false.
     * @return bool|int The priority of that hook is returned, or false if the function is not attached.
     */
    public static function hasFilter($tag, $functionToCheck = null)
    {
        /** @var HookManager $instance */
        $instance = self::getInstance(HookManager::class);
        return $instance->hasFilter($tag, $functionToCheck);
    }
}
