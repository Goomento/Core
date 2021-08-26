<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Helper;

use Goomento\Core\Traits\TraitStaticInstances;

/**
 * Class ObjectManager
 * @package Goomento\Core\Helper
 */
class ObjectManager
{
    use TraitStaticInstances;

    /**
     * @param $type
     * @return mixed
     */
    public static function get($type)
    {
        return self::getObjectManager()->get($type);
    }

    /**
     * @param $type
     * @param array $arguments
     * @return mixed
     */
    public static function create($type, array $arguments = [])
    {
        return self::getObjectManager()->create($type, $arguments);
    }
}
