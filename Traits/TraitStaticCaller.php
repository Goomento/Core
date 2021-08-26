<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Traits;

use Magento\Framework\App\ObjectManager;

/**
 * Trait TraitStaticCaller
 * @package Goomento\Core\Traits
 */
trait TraitStaticCaller
{
    /**
     * @var mixed
     */
    private static $staticInstance;

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($name, $arguments)
    {
        if (!self::$staticInstance = null) {
            $instance = self::getStaticInstance();
            if (is_string($instance)) {
                $objectManager = ObjectManager::getInstance();
                $instance = $objectManager->get($instance);
            }

            self::$staticInstance = $instance;
        }
        return self::$staticInstance->{$name}(...$arguments);
    }

    /**
     * @return object|string
     */
    abstract static protected function getStaticInstance();
}
