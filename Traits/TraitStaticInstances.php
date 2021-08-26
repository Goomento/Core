<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Traits;

use Magento\Framework\App\ObjectManager;

/**
 * Trait TraitInstances
 * @package Goomento\Core\Traits
 */
trait TraitStaticInstances
{
    /**
     * @param null $type
     * @return mixed
     */
    private static function getInstance($type = null)
    {
        return self::getObjectManager()->get($type ?? __CLASS__);
    }

    /**
     * @return ObjectManager
     */
    private static function getObjectManager(): ObjectManager
    {
        return ObjectManager::getInstance();
    }
}
