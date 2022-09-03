<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model\HtmlModifier;

use Goomento\Core\Api\ModifierInterface;
use Goomento\Core\Helper\HooksHelper;

class BodyClasses implements ModifierInterface
{
    /**
     * Add body class to HTML output
     *
     * @param $data
     * @return array|object|string
     */
    public function modify($data)
    {
        $data = (string) $data;
        $bodyClasses = HooksHelper::applyFilters('body_classes', [])->getResult();
        if (!empty($bodyClasses)) {
            if (preg_match('/<body[^>]+?>/i', $data, $matches)) {
                $bodyClasses = implode(' ', array_values($bodyClasses));
                $bodyTag = $matches[0];
                $parts = explode('class="', $bodyTag);
                if (count($parts)) {
                    $parts[1] = $bodyClasses . ' ' . $parts[1];
                    $newBodyTag = implode('class="', $parts);
                } else {
                    $newBodyTag = substr($bodyTag, strlen($bodyTag) - 1) . 'class="' . $bodyClasses . '" >';
                }

                $data = str_replace($bodyTag, $newBodyTag, $data);
            }
        }
        return $data;
    }
}
