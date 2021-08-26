<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Plugin\App\Action\AbstractAction;

use Goomento\Core\Model\Initializer;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\RequestInterface;

/**
 * Class Predispatch
 * @package Goomento\Core\Plugin\App\Action\AbstractAction
 */
class Predispatch
{
    /**
     * @var Initializer
     */
    private $initializer;

    /**
     * Initializer constructor.
     * @param Initializer $initializer
     */
    public function __construct(
        Initializer $initializer
    ) {
        $this->initializer = $initializer;
    }

    /**
     * @param AbstractAction $subject
     * @param RequestInterface $request
     */
    public function beforeDispatch(
        AbstractAction $subject,
        RequestInterface $request
    )
    {
        $data = [
            'controller_action' => $subject,
            'request' => $request
        ];
        $this->initializer->execute($data);
    }
}
