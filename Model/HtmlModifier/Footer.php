<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model\HtmlModifier;

use Goomento\Core\Api\ModifierInterface;
use Goomento\Core\Helper\HooksHelper;

class Footer implements ModifierInterface
{
    /**
     * Add JS/CSS to footer of specific page
     *
     * @param $data
     * @return string
     */
    public function modify($data)
    {
        $data = (string) $data;
        if (!empty($data)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            ob_start();
            HooksHelper::doAction('footer');
            $footer = ob_get_clean();
            if (!empty($footer) && preg_match('/<\/body[^>]*?>/i', $data, $matches)) {
                $data = str_replace($matches[0], $footer . $matches[0], $data);
            }
        }

        return $data;
    }
}
