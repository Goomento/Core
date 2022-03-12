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

class ControllersDispatch
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
        $this->initializer->processing($data);
    }

    /**
     * @param AbstractAction $subject
     * @param $result
     * @return mixed
     */
    public function afterDispatch(
        AbstractAction $subject,
        $result
    )
    {
        $data = [
            'controller_action' => $subject,
            'result' => $result
        ];
        $this->initializer->endProcessing($data);
        return $result;
    }
}
