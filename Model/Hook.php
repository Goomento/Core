<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

use ArrayAccess;
use Iterator;
use ReflectionFunction;

class Hook implements Iterator, ArrayAccess
{
    /**
     * Hook callbacks.
     *
     */
    private $callbacks = [];

    /**
     * The priority keys of actively running iterations of a hook.
     *

     * @var array
     */
    private $iterations = [];

    /**
     * The current priority of actively running iterations of a hook.
     *

     * @var array
     */
    private $currentPriority = [];

    /**
     * Number of levels this hook can be recursively called.
     *

     * @var int
     */
    private $nestingLevel = 0;

    /**
     * Flag for if we're current doing an action, rather than a filter.
     *
     * @var bool
     */
    private $doingAction = false;

    /**
     * @var Transport
     */
    private $transport;

    /**
     * Hook Constructor
     */
    public function __construct()
    {
        $this->transport = new Transport([]);
    }

    /**
     * Reveal the callbacks
     *
     * @return array
     */
    public function getCallBacks()
    {
        return $this->callbacks;
    }

    /**
     * Hooks a function or method to a specific filter action.
     * @return Transport
     */
    public function addFilter(string $tag, $functionToAdd, int $priority)
    {
        $idx = $this->uniqueId($tag, $functionToAdd, $priority);
        $priorityExisted = isset($this->callbacks[$priority]);

        $this->callbacks[$priority][$idx] = [
            'function' => $functionToAdd,
        ];

        // if we're adding a new priority to the list, put them back in sorted order
        if (!$priorityExisted && count($this->callbacks) > 1) {
            ksort($this->callbacks, SORT_NUMERIC);
        }

        if ($this->nestingLevel > 0) {
            $this->resortActiveIterations($priority, $priorityExisted);
        }

        return $this->transport;
    }

    /**
     * Handles resetting callback priority keys mid-iteration.
     *
     * @param bool|int $newPriority Optional. The priority of the new filter being added. Default false,
     *                                   for no priority being added.
     * @param bool $priorityExisted Optional. Flag for whether the priority already existed before the new
     *                                   filter was added. Default false.
     *
     */
    private function resortActiveIterations($newPriority = false, bool $priorityExisted = false)
    {
        $newPriorities = array_keys($this->callbacks);

        // If there are no remaining hooks, clear out all running iterations.
        if (!$newPriorities) {
            foreach ($this->iterations as $index => $iteration) {
                $this->iterations[$index] = $newPriorities;
            }
            return;
        }

        $min = min($newPriorities);
        foreach ($this->iterations as $index => &$iteration) {
            $current = current($iteration);
            // If we're already at the end of this iteration, just leave the array pointer where it is.
            if (false === $current) {
                continue;
            }

            $iteration = $newPriorities;

            if ($current < $min) {
                array_unshift($iteration, $current);
                continue;
            }

            while (current($iteration) < $current) {
                if (false === next($iteration)) {
                    break;
                }
            }

            if ($newPriority === $this->currentPriority[$index] && !$priorityExisted) {
                /*
                 * ... and the new priority is the same as what $this->iterations thinks is the previous
                 * priority, we need to move back to it.
                 */

                if (false === current($iteration)) {
                    // If we've already moved off the end of the array, go back to the last element.
                    $prev = end($iteration);
                } else {
                    // Otherwise, just go back to the previous element.
                    $prev = prev($iteration);
                }
                if (false === $prev) {
                    // Start of the array. Reset, and go about our day.
                    reset($iteration);
                } elseif ($newPriority !== $prev) {
                    // Previous wasn't the same. Move forward again.
                    next($iteration);
                }
            }
        }
        unset($iteration);
    }

    /**
     * Unhooks a function or method from a specific filter action.
     *
     * @param string $tag The filter hook to which the function to be removed is hooked. Used
     *                                     for building the callback ID when SPL is not available.
     * @param callable $functionToRemove The callback to be removed from running when the filter is applied.
     * @param int $priority The exact priority used when adding the original filter callback.
     * @return bool Whether the callback existed before it was removed.
     *
     */
    public function removeFilter(string $tag, callable $functionToRemove, int $priority)
    {
        $functionKey = $this->uniqueId($tag, $functionToRemove, $priority);

        $exists = isset($this->callbacks[$priority][$functionKey]);
        if ($exists) {
            unset($this->callbacks[$priority][$functionKey]);
            if (!$this->callbacks[$priority]) {
                unset($this->callbacks[$priority]);
                if ($this->nestingLevel > 0) {
                    $this->resortActiveIterations();
                }
            }
        }
        return $exists;
    }

    /**
     * Checks if a specific action has been registered for this hook.
     *
     * @param string $tag Optional. The name of the filter hook. Used for building
     *                                         the callback ID when SPL is not available. Default empty.
     * @param callable|bool $functionToCheck Optional. The callback to check for. Default false.
     * @return bool|int The priority of that hook is returned, or false if the function is not attached.
     */
    public function hasFilter(string $tag = '', $functionToCheck = false)
    {
        if (false === $functionToCheck) {
            return $this->hasFilters();
        }

        $functionKey = $this->uniqueId($tag, $functionToCheck, false);
        if (!$functionKey) {
            return false;
        }

        foreach ($this->callbacks as $priority => $callbacks) {
            if (isset($callbacks[$functionKey])) {
                return $priority;
            }
        }

        return false;
    }

    /**
     * Checks if any callbacks have been registered for this hook.
     *
     * @return bool True if callbacks have been registered for the current hook, otherwise false.
     *
     */
    public function hasFilters(): bool
    {
        foreach ($this->callbacks as $callbacks) {
            if ($callbacks) {
                return true;
            }
        }
        return false;
    }

    /**
     * Removes all callbacks from the current filter.
     *
     * @param int|bool $priority Optional. The priority number to remove. Default false.
     *
     */
    public function removeAllFilters($priority = false)
    {
        if (!$this->callbacks) {
            return;
        }

        if (false === $priority) {
            $this->callbacks = [];
        } elseif (isset($this->callbacks[$priority])) {
            unset($this->callbacks[$priority]);
        }

        if ($this->nestingLevel > 0) {
            $this->resortActiveIterations();
        }
    }

    /**
     * Build Unique ID for storage and retrieval.
     * @throws \ReflectionException
     */
    private function uniqueId(string $tag, $function, $priority)
    {
        $keys = [];
        if (is_string($function)) {
            $keys['function'] = $function;
        }

        if (is_object($function)) {
            // Closures are currently implemented as objects
            $function = [$function, ''];
        } else {
            $function = (array) $function;
        }

        if ($function[0] instanceof \Closure) {
            $ref = new \ReflectionFunction($function[0]);
            $keys['closure'] = $ref->getName();
            $keys['namespace'] = $ref->getNamespaceName();
            $keys['start_line'] = $ref->getStartLine();
            $keys['end_line'] = $ref->getEndLine();
            if ($args = $ref->getParameters()) {
                foreach ($args as $index => $arg) {
                    $keys['arg_' . $index] = $arg->getName();
                }
            }
        } elseif (is_object($function[0])) {
            $keys['class'] = get_class($function[0]);
            $keys['hash'] = spl_object_hash($function[0]);
        } elseif (is_string($function[0])) {
            $keys['class'] = $function[0];
        }

        if ($function[1]) {
            $keys['method'] = (string) $function[1];
        }

        if (!empty($keys)) {
            return implode('::', $keys);
        }

        return null;
    }

    /**
     * @param $value
     * @param array $args
     * @return Transport
     */
    public function applyFilters($value, array $args)
    {
        $transport = $this->transport;

        $transport->setData('_args', $args);
        $transport->setData('_value', $value);

        $transport->setResult($value);

        if (!$this->callbacks) {
            return $transport;
        }

        $nestingLevel = $this->nestingLevel++;

        $this->iterations[$nestingLevel] = array_keys($this->callbacks);

        $args[] = $transport;

        $valueClass =  is_object($value) ? get_class($value) : null;

        do {
            $this->currentPriority[$nestingLevel] = current($this->iterations[$nestingLevel]);

            $priority = $this->currentPriority[$nestingLevel];

            foreach ($this->callbacks[$priority] as $the_) {
                if (!$this->doingAction) {
                    $args[0] = $value;
                }

                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                $value = call_user_func($the_['function'], ...$args);

                if ($value === null) { // Return void
                    $value = $transport->getData('_value');
                } else {
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    $valueType = gettype($value);
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    $resultType = gettype($transport->getResult());

                    $transport->setResult($value);

                    if ($valueType !== $resultType || // Not the same type
                        ($valueType === 'object' && $valueClass && get_class($value) !== $valueClass)) { // Not the same class
                        $value = $transport->getData('_value');
                    }
                }
            }
        } while (false !== next($this->iterations[$nestingLevel]));

        unset($this->iterations[$nestingLevel]);
        unset($this->currentPriority[$nestingLevel]);

        $this->nestingLevel--;

        return $transport;
    }

    /**
     * Calls the callback functions that have been added to an action hook.
     *
     * @param array $args Parameters to pass to the callback functions.
     */
    public function doAction(array $args)
    {
        $this->doingAction = true;
        $this->applyFilters('', $args);

        // If there are recursive calls to the current action, we haven't finished it until we get to the last one.
        if (!$this->nestingLevel) {
            $this->doingAction = false;
        }
    }

    /**
     * Processes the functions hooked into the 'all' hook.
     *
     * @param array $args Arguments to pass to the hook callbacks. Passed by reference.
     */
    public function doAllHook(array &$args)
    {
        $nestingLevel = $this->nestingLevel++;
        $this->iterations[$nestingLevel] = array_keys($this->callbacks);

        do {
            $priority = current($this->iterations[$nestingLevel]);
            foreach ($this->callbacks[$priority] as $the_) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                call_user_func($the_['function'], ...$args);
            }
        } while (false !== next($this->iterations[$nestingLevel]));

        unset($this->iterations[$nestingLevel]);
        $this->nestingLevel--;
    }

    /**
     * @return false|mixed
     */
    public function currentPriority()
    {
        if (false === current($this->iterations)) {
            return false;
        }

        return current(current($this->iterations));
    }

    /**
     * Determines whether an offset value exists.
     *
     * @param mixed $offset An offset to check for.
     * @return bool True if the offset exists, false otherwise.
     *
     */
    public function offsetExists($offset) : bool
    {
        return isset($this->callbacks[$offset]);
    }

    /**
     * Retrieves a value at a specified offset.
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed If set, the value at the specified offset, null otherwise.

     *
     * @link https://secure.php.net/manual/en/arrayaccess.offsetget.php
     *
     */
    public function offsetGet($offset) : mixed
    {
        return $this->callbacks[$offset] ?? null;
    }

    /**
     * Sets a value at a specified offset.
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     *
     * @link https://secure.php.net/manual/en/arrayaccess.offsetset.php
     *
     */
    public function offsetSet($offset, $value) : void
    {
        if (is_null($offset)) {
            $this->callbacks[] = $value;
        } else {
            $this->callbacks[$offset] = $value;
        }
    }

    /**
     * Unsets a specified offset.
     *
     * @param mixed $offset The offset to unset.
     * @link https://secure.php.net/manual/en/arrayaccess.offsetunset.php
     *
     *
     */
    public function offsetUnset($offset) : void
    {
        unset($this->callbacks[$offset]);
    }

    /**
     * Returns the current element.
     *
     * @return array Of callbacks at current priority.
     * @link https://secure.php.net/manual/en/iterator.current.php
     *
     *
     */
    public function current() : mixed
    {
        return current($this->callbacks);
    }

    /**
     * Moves forward to the next element.
     *
     * @return void Of callbacks at next priority.
     * @link https://secure.php.net/manual/en/iterator.next.php
     *
     */
    public function next() : void
    {
        next($this->callbacks);
    }

    /**
     * Returns the key of the current element.
     *
     * @return mixed Returns current priority on success, or NULL on failure
     * @link https://secure.php.net/manual/en/iterator.key.php
     *
     *
     */
    public function key() : mixed
    {
        return key($this->callbacks);
    }

    /**
     * Checks if current position is valid.
     *
     * @return boolean
     * @link https://secure.php.net/manual/en/iterator.valid.php
     *
     *
     */
    public function valid() : bool
    {
        return key($this->callbacks) !== null;
    }

    /**
     * Rewinds the Iterator to the first element.
     *
     *
     * @link https://secure.php.net/manual/en/iterator.rewind.php
     */
    public function rewind() : void
    {
        reset($this->callbacks);
    }
}
