<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Plugin\App\ActionInterface;

use Goomento\Core\Model\Initializer;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class PreExecute
 * @package Goomento\Core\Plugin\App\ActionInterface
 */
class PreExecute
{
    /**
     * @var Initializer
     */
    private $initializer;
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Initializer constructor.
     * @param Initializer $initializer
     * @param RequestInterface $request
     */
    public function __construct(
        Initializer $initializer,
        RequestInterface $request
    ) {
        $this->initializer = $initializer;
        $this->request = $request;
    }

    /**
     * @param ActionInterface $action
     */
    public function beforeExecute(
        ActionInterface $action
    )
    {
        $data = [
            'controller_action' => $action,
            'request' => $this->request
        ];
        $this->initializer->execute($data);
    }
}
