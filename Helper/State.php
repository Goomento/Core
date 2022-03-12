<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;

class State extends AbstractHelper
{
    /**
     * @var AppState
     */
    protected $state;

    /**
     * State constructor.
     * @param Context $context
     * @param AppState $state
     */
    public function __construct(
        Context $context,
        AppState $state
    )
    {
        parent::__construct($context);
        $this->state = $state;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getAreaCode(): string
    {
        return $this->state->getAreaCode();
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function isAdminhtml(): bool
    {
        return $this->getAreaCode() === Area::AREA_ADMINHTML;
    }
}
