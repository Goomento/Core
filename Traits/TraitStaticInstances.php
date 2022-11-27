<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Traits;

use Magento\Framework\App\ObjectManager;

// phpcs:disable Magento2.Functions.StaticFunction.StaticFunction
trait TraitStaticInstances
{
    /**
     * @param string|null $type
     * @return mixed
     */
    private static function getInstance(?string $type = null)
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
