<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Api;

interface ModifierInterface
{
    /**
     * Modify the data
     *
     * @param mixed $data
     * @return mixed
     */
    public function modify($data);
}
