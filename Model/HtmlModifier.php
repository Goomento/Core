<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

use Goomento\Core\Api\ModifierInterface;
use Goomento\Core\Helper\HooksHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

class HtmlModifier implements ModifierInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var array
     */
    private $modifiers;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $modifiers
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        array $modifiers = []
    )
    {
        $this->objectManager = $objectManager;
        if (empty($modifiers)) {
            $modifiers = [
                'body_classes' => HtmlModifier\BodyClasses::class,
                'header' => HtmlModifier\Header::class,
                'footer' => HtmlModifier\Footer::class,
            ];
        }
        $this->modifiers = $modifiers;
    }

    /**
     * Modify HTML output
     *
     * @param $data
     * @return string
     * @throws LocalizedException
     */
    public function modify($data)
    {
        $print = HooksHelper::applyFilters('print_resources', true);
        if ($print === true) {
            $data = (string) $data;
            foreach ($this->modifiers as $modifier) {
                if (is_string($modifier)) {
                    $modifier = $this->objectManager->get($modifier);
                }
                if (!($modifier instanceof ModifierInterface)) {
                    throw new LocalizedException(
                        __('Invalid Modifier Class.')
                    );
                }

                $data = $modifier->modify($data);
            }
        }

        return $data;
    }
}
