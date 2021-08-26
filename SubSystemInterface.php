<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core;

/**
 * Interface SubSystemInterface
 * @package Goomento\Core
 */
interface SubSystemInterface
{
    /**
     * @param array $buildSubject
     */
    public function init(array $buildSubject);

    /**
     * Allow ['*'] or ['adminhtml', 'frontend', 'ajax', '{{ YOUR CUSTOM CONTROLLER NAME }}']
     * @return array|string
     */
    public function getAreaScopes();
}
