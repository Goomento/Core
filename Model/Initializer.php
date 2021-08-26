<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

use Goomento\Core\SubSystemInterface;

/**
 * Class Initializer
 * @package Goomento\Core\Model
 */
class Initializer
{
    /**
     * @var bool
     */
    private $processed = false;

    /**
     * @var SubSystemInterface
     */
    protected $subSystem;

    /**
     * Initializer constructor.
     * @param SubSystemInterface $subSystem
     */
    public function __construct(
        SubSystemInterface $subSystem
    ) {
        $this->subSystem = $subSystem;
    }

    /**
     * @param array $data
     */
    public function execute(array $data) : void
    {
        if (false === $this->processed) {
            $this->processed  = true;
            $this->_execute($data);
        }
    }

    /**
     * @param $data
     */
    private function _execute($data)
    {
        $this->subSystem->init($data);
    }
}
