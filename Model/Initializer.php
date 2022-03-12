<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core\Model;

use Goomento\Core\Helper\HooksHelper;
use Goomento\Core\SubSystemInterface;

class Initializer
{
    /**
     * @var bool
     */
    private $processing = null;

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
     * Start processing the program
     *
     * @param array $data
     */
    public function processing(array $data) : void
    {
        if (null === $this->processing) {
            /**
             * Use for hook into system
             */
            HooksHelper::doAction('core/start');

            $this->processing  = true;
            $this->subSystem->init($data);
        }
    }

    /**
     * End processing the program
     */
    public function endProcessing(?array $data = null)
    {
        if ($this->processing === true) {
            $this->processing = false;

            /**
             * Use for hook into system
             */
            HooksHelper::doAction('core/end', $data);
        }
    }
}
