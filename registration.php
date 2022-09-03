<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Goomento_Core',
    isset($file) ? dirname($file) : __DIR__
);
