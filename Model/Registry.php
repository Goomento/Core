<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

class Registry
{
    /**
     * Registry collection
     *
     * @var array
     */
    private $_registry = [];

    /**
     * Retrieve a value from registry by a key
     *
     * @param string $key
     * @return mixed
     *
     */
    public function registry(string $key)
    {
        if (isset($this->_registry[$key])) {
            return $this->_registry[$key];
        }
        return null;
    }

    /**
     * Register a new variable
     *
     * @param string $key
     * @param mixed $value
     * @param bool $graceful
     * @return void
     * @throws \RuntimeException
     *
     */
    public function register(string $key, $value, bool $graceful = false)
    {
        if (isset($this->_registry[$key])) {
            if ($graceful) {
                return;
            }
            throw new \RuntimeException('Registry key "' . $key . '" already exists');
        }
        $this->_registry[$key] = $value;
    }

    /**
     * Unregister a variable from register by key
     *
     * @param string $key
     * @return void
     *
     */
    public function unregister(string $key)
    {
        if (isset($this->_registry[$key])) {
            if (is_object($this->_registry[$key])
                && method_exists($this->_registry[$key], '__destruct')
                && is_callable([$this->_registry[$key], '__destruct'])
            ) {
                $this->_registry[$key]->__destruct();
            }
            unset($this->_registry[$key]);
        }
    }

    /**
     * Destruct registry items
     */
    public function __destruct()
    {
        $keys = array_keys($this->_registry);
        array_walk($keys, [$this, 'unregister']);
    }
}
