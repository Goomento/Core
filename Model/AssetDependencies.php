<?php /** @noinspection ALL */
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

// phpcs:disable Magento2.CodeAnalysis.EmptyBlock.DetectedFunction
abstract class AssetDependencies
{
    /**
     * An array of registered handle objects.
     *
     * @var array[[]]
     */
    protected $registered = [];

    /**
     *
     * @var array[]
     */
    protected $queue = [];

    /**
     *
     * @var array
     */
    protected $toDo = [];

    /**
     *
     * @var array
     */
    protected $done = [];

    /**
     * An array of additional arguments passed when a handle is registered.
     *
     * Arguments are appended to the item query string.
     *
     * @var array
     */
    protected $args = [];

    /**
     * An array of handle groups to enqueue.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * ScriptsManager constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Init the asset
     */
    protected function init()
    {
    }

    /**
     * Processes the items and dependencies.
     *
     * Processes the items passed to it or the queue, and their dependencies.
     *
     *
     * @param mixed $handles Optional. Items to be processed: Process queue (false), process item (string), process items (array of strings).
     * @param mixed $group   Group level: level (int), no groups (false).
     * @return array Handles of items that have been processed.
     */
    public function doItems($handles = false, $group = false)
    {
        $handles = false === $handles ? $this->queue : (array) $handles;
        $this->allDeps($handles);

        foreach ($this->toDo as $key => $handle) {
            if (! in_array($handle, $this->done, true) && isset($this->registered[ $handle ])) {
                if ($this->doItem($handle, $group)) {
                    $this->done[] = $handle;
                }

                unset($this->toDo[ $key ]);
            }
        }

        return $this->done;
    }

    /**
     * Processes a dependency.
     *
     * @param string $handle Name of the item. Should be unique.
     * @return bool True on success, false if not set.
     */
    public function doItem(string $handle, $group = false): bool
    {
        return isset($this->registered[ $handle ]);
    }

    /**
     * Determines dependencies.
     *
     * Recursively builds an array of items to process taking
     * dependencies into account. Does NOT catch infinite loops.
     *
     *
     * @param mixed     $handles   Item handle and argument (string) or item handles and arguments (array of strings).
     * @param bool      $recursion Internal flag that function is calling itself.
     * @param int|false $group     Group level: (int) level, (false) no groups.
     * @return bool True on success, false on failure.
     */
    public function allDeps($handles, $recursion = false, $group = false) : bool
    {
        $handles = (array) $handles;
        if (! $handles) {
            return false;
        }

        foreach ($handles as $handle) {
            $handleParts = explode('?', $handle);
            $handle       = $handleParts[0];
            $queued       = in_array($handle, $this->toDo, true);

            if (in_array($handle, $this->done, true)) { // Already done
                continue;
            }

            $moved     = $this->setGroup($handle, $recursion, $group);
            $newGroup = $this->groups[ $handle ];

            if ($queued && ! $moved) { // already queued and in the right group
                continue;
            }

            $keepGoing = true;
            if (! isset($this->registered[ $handle ])) {
                $keepGoing = false; // Item doesn't exist.
            } elseif ($this->registered[ $handle ]['deps'] && array_diff($this->registered[ $handle ]['deps'], array_keys($this->registered))) {
                $keepGoing = false; // Item requires dependencies that don't exist.
            } elseif ($this->registered[ $handle ]['deps'] && ! $this->allDeps($this->registered[ $handle ]['deps'], true, $newGroup)) {
                $keepGoing = false; // Item requires dependencies that don't exist.
            }

            if (! $keepGoing) { // Either item or its dependencies don't exist.
                if ($recursion) {
                    return false; // Abort this branch.
                } else {
                    continue; // We're at the top level. Move on to the next one.
                }
            }

            if ($queued) { // Already grabbed it and its dependencies.
                continue;
            }

            if (isset($handleParts[1])) {
                $this->args[ $handle ] = $handleParts[1];
            }

            $this->toDo[] = $handle;
        }

        return true;
    }

    /**
     * Register an item.
     *
     * Registers the item if no item of that name already exists.
     *
     *
     * @param string           $handle Name of the item. Should be unique.
     * @param string|bool      $src    Full URL of the item, or path of the item.
     *                                 If source is set to false, item is an alias of other items it depends on.
     * @param string[]         $deps   Optional. An array of registered item handles this item depends on. Default empty array.
     * @param string|bool|null $ver    Optional. String specifying item version number, if it has one, which is added to the URL
     *                                 as a query string for cache busting purposes.
     *                                 If set to null, no version is added.
     * @param mixed            $args   Optional. Custom property of the item. NOT the class property $args..
     */
    public function add(string $handle, $src, array $deps = [], $ver = false, $args = null)
    {
        if (isset($this->registered[ $handle ])) {
            return false;
        }
        $this->registered[ $handle ] = [
            'handle' => $handle,
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'args' => $args,
            'extra' => [],
        ];
    }

    /**
     * Add extra item data.
     *
     * Adds data to a registered item.
     *
     * @param string $handle Name of the item. Should be unique.
     * @param string $key    The data key.
     * @param mixed  $value  The data value.
     * @return bool True on success, false on failure.
     */
    public function addData(string $handle, string $key, $value)
    {
        if (! isset($this->registered[ $handle ])) {
            return false;
        }

        $this->registered[ $handle ]['extra'][$key] = $value;
        return true;
    }

    /**
     * Get extra item data.
     *
     * Gets data associated with a registered item.
     *
     *
     * @param string $handle Name of the item. Should be unique.
     * @param string $key    The data key.
     * @return mixed Extra item data (string), false otherwise.
     */
    public function getData(string $handle, string $key)
    {
        if (! isset($this->registered[ $handle ])) {
            return false;
        }

        if (! isset($this->registered[ $handle ]['extra'][ $key ])) {
            return false;
        }

        return $this->registered[ $handle ]['extra'][ $key ];
    }

    /**
     * Un-register an item or items.
     *
     * @param mixed $handles Item handle and argument (string) or item handles and arguments (array of strings).
     * @return void
     */
    public function remove($handles)
    {
        foreach ((array) $handles as $handle) {
            if (isset($this->registered[$handle])) {
                unset($this->registered[ $handle ]);
            }
        }
    }

    /**
     * Queue an item or items.
     *
     * Decodes handles and arguments, then queues handles and stores
     * arguments in the class property $args. For example in extending
     * classes, $args is appended to the item url as a query string.
     * Note $args is NOT the $args property of items in the $registered array.
     *
     * @param mixed $handles Item handle and argument (string) or item handles and arguments (array of strings).
     */
    public function enqueue($handles)
    {
        foreach ((array) $handles as $handle) {
            $handle = explode('?', $handle);
            if (! in_array($handle[0], $this->queue) && isset($this->registered[ $handle[0] ])) {
                $this->queue[] = $handle[0];
                if (isset($handle[1])) {
                    $this->args[ $handle[0] ] = $handle[1];
                }
            }
        }
    }

    /**
     * Dequeue an item or items.
     *
     * Decodes handles and arguments, then dequeues handles
     * and removes arguments from the class property $args.
     *
     *
     * @param mixed $handles Item handle and argument (string) or item handles and arguments (array of strings).
     */
    public function dequeue($handles)
    {
        foreach ((array) $handles as $handle) {
            $handle = explode('?', $handle);
            $key    = array_search($handle[0], $this->queue);
            if (false !== $key) {
                unset($this->queue[ $key ]);
                unset($this->args[ $handle[0] ]);
            }
        }
    }

    /**
     * Recursively search the passed dependency tree for $handle
     *
     * @param string[] $queue  An array of queued _WP_Dependency handles.
     * @param string $handle Name of the item. Should be unique.
     * @return bool Whether the handle is found after recursively searching the dependency tree.
     */
    protected function recurseDeps(array $queue, string $handle)
    {
        foreach ($queue as $queued) {
            if (! isset($this->registered[ $queued ])) {
                continue;
            }

            if (in_array($handle, $this->registered[ $queued ]->deps)) {
                return true;
            } elseif ($this->recurseDeps($this->registered[ $queued ]->deps, $handle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set item group, unless already in a lower group.
     *
     * @param string $handle    Name of the item. Should be unique.
     * @param bool   $recursion Internal flag that calling function was called recursively.
     * @param mixed  $group     Group level.
     * @return bool Not already in the group or a lower group
     */
    public function setGroup(string $handle, bool $recursion, $group) : bool
    {
        $group = (int) $group;

        if (isset($this->groups[ $handle ]) && $this->groups[ $handle ] <= $group) {
            return false;
        }

        $this->groups[ $handle ] = $group;

        return true;
    }
}
