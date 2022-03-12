<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

use Goomento\Core\Helper\HooksHelper;
use Goomento\Core\Helper\State;
use Goomento\Core\SubSystemInterface;
use Magento\Framework\App\Request\Http;

class Theme implements SubSystemInterface
{
    const THEMING_PRIORITY = 9;

    /**
     * @var State
     */
    private $stateHelper;

    /**
     * Theme constructor.
     * @param State $stateHelper
     */
    public function __construct(
        State $stateHelper
    )
    {
        $this->stateHelper = $stateHelper;
    }

    /**
     * @inheritdoc
     */
    public function init(array $buildSubject)
    {
        /** @var Http $request */
        $request = $buildSubject['request'];
        /** @var string $areaCode */
        $areaCode = $this->stateHelper->getAreaCode();

        if ($request->isAjax()) {
            HooksHelper::addAction('init', function () {
                HooksHelper::doAction('ajax/init', self::THEMING_PRIORITY);
            });
        }

        HooksHelper::addAction('header', function () use ($areaCode) {
            HooksHelper::doAction("header/{$areaCode}", self::THEMING_PRIORITY);
        });

        HooksHelper::addAction('footer', function () use ($areaCode) {
            HooksHelper::doAction("footer/{$areaCode}", self::THEMING_PRIORITY);
        });
    }

    /**
     * @inheritdoc
     */
    public function getAreaScopes()
    {
        return ['frontend', 'adminhtml'];
    }
}
