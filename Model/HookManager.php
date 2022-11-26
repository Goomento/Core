<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

class HookManager
{
    /**
     * @var Hook[]
     */
    private $hooks = [];

    /**
     * @var array
     */
    private $didActions = [];

    /**
     * @var string[]
     */
    private $currentFilter = [];

    /**
     * @param $tag
     * @param $functionToAdd
     * @param int $priority
     * @return Transport
     */
    public function addFilter($tag, $functionToAdd, int $priority = 10): Transport
    {
        if (! isset($this->hooks[ $tag ])) {
            $this->hooks[ $tag ] = new Hook();
        }
        return $this->hooks[ $tag ]->addFilter($tag, $functionToAdd, $priority);
    }

    /**
     * @return Transport
     */
    public function applyFilters() : Transport
    {
        $args = func_get_args();

        $tag = $args[0];

        $value = $args[1] ?? null;

        $filtered = $value;

        if (isset($this->hooks[$tag])) {

            $this->currentFilter[] = $tag;

            array_shift($args);

            $filtered = $this->hooks[ $tag ]->applyFilters($value, $args);

            array_pop($this->currentFilter);
        } else {
            $filtered = (new Transport())->setResult($filtered);
        }

        return $filtered;
    }

    /**
     * @param $tag
     * @param callable|null $functionToRemove
     * @param int $priority
     * @return bool
     */
    public function removeFilter($tag, $functionToRemove = null, int $priority = 10): bool
    {
        $result = false;
        if (isset($this->hooks[ $tag ])) {
            if ($functionToRemove) {
                $result = $this->hooks[ $tag ]->removeFilter($tag, $functionToRemove, $priority);
                if (! $this->hooks[ $tag ]->getCallBacks()) {
                    unset($this->hooks[ $tag ]);
                }
            } else {
                unset($this->hooks[ $tag ]);
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @param $tag
     * @param $functionToAdd
     * @param int $priority
     * @return Transport
     */
    public function addAction($tag, $functionToAdd, int $priority = 10)
    {
        return $this->addFilter($tag, $functionToAdd, $priority);
    }

    /**
     * @param $tag
     * @param mixed ...$arg
     * @return void
     */
    public function doAction($tag, ...$arg)
    {
        if (isset($this->didActions[$tag])) {
            ++$this->didActions[$tag];
        } else {
            $this->didActions[$tag] = 1;
        }

        if (isset($this->hooks[$tag])) {
            $this->currentFilter[] = $tag;

            if (empty($arg)) {
                $arg[] = '';
            } elseif (is_array($arg[0]) && 1 === count($arg[0]) && isset($arg[0][0]) && is_object($arg[0][0])) {
                // Backward compatibility for PHP4-style passing of `array( &$this )` as action `$arg`.
                $arg[0] = $arg[0][0];
            }

            $this->hooks[ $tag ]->doAction($arg);

            array_pop($this->currentFilter);
        }
    }

    /**
     * @param $tag
     * @param null $functionToCheck
     * @return bool
     */
    public function hasFilter($tag, $functionToCheck = null)
    {
        $result = isset($this->hooks[$tag]);
        if ($functionToCheck && $result) {
            $result = $this->hooks[$tag]->hasFilter($tag, $functionToCheck);
        }

        return $result;
    }

    /**
     * @param $tag
     * @return int|mixed
     */
    public function didAction($tag)
    {
        return $this->didActions[$tag] ?? 0;
    }
}
