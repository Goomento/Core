<?php
/**
 * @package Goomento_Core
 * @link https://github.com/Goomento/Core
 */

declare(strict_types=1);

namespace Goomento\Core;

use Goomento\Core\Helper\Hooks;
use Goomento\Core\Helper\State;
use Goomento\Core\Model\Registry;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class SubSystem
 * @package Goomento\Core
 */
class SubSystem implements SubSystemInterface
{
    /**
     * @var State
     */
    private $stateHelper;

    /**
     * @var array
     */
    private $systems;
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ActionInterface|null
     */
    private $action;

    /**
     * @var Http|null
     */
    private $request;
    /**
     * @var Registry
     */
    private $registry;

    /**
     * SubSystem constructor.
     * @param State $stateHelper
     * @param ObjectManagerInterface $objectManager
     * @param Registry $registry
     * @param array $systems
     */
    public function __construct(
        State $stateHelper,
        ObjectManagerInterface $objectManager,
        Registry $registry,
        array $systems = []
    ) {
        $this->stateHelper = $stateHelper;
        $this->objectManager = $objectManager;
        $this->systems = $systems;
        $this->registry = $registry;

        /**
         * Construct the sub-system
         */
        Hooks::doAction('construct');
    }

    /**
     * @inheritdoc
     */
    public function init(array $buildSubject)
    {
        $this->request = $buildSubject['request'];
        $this->action = $buildSubject['controller_action'];

        $this->registry->register('current_action', $this->action);
        $this->registry->register('current_action_name', $this->request->getFullActionName());

        foreach ($this->systems as $system) {
            /** @var SubSystemInterface $system */
            $system = $this->getSubsystem($system);

            if ($this->matchingAreaScopes($system)) {
                $system->init($buildSubject);
            }
        }

        Hooks::doAction('init');
    }

    /**
     * @param SubSystemInterface $subSystem
     * @return bool
     * @throws LocalizedException
     */
    private function matchingAreaScopes(SubSystemInterface $subSystem) : bool
    {
        $scopes = (array) $subSystem->getAreaScopes();
        $scopes = array_flip($scopes);
        $currentState = $this->stateHelper->getAreaCode();
        $fullActionName = $this->request->getFullActionName();

        if (!empty($scopes)) {
            if (isset($scopes['*'])) {
                return true;
            } elseif (in_array($currentState, ['adminhtml', 'frontend']) && isset($scopes[$currentState])) {
                if ($this->request->isAjax()) {
                    if (isset($scopes['ajax'])) {
                        return true;
                    }
                } else {
                    return true;
                }
            } elseif (isset($scopes[$fullActionName])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $name
     * @return SubSystemInterface
     * @throws LocalizedException
     */
    private function getSubsystem($name)
    {
        $object = $this->objectManager->get($name);
        if (!($object instanceof SubSystemInterface)) {
            throw new LocalizedException(
                __('Class `%1` must implement `SubSystemInterface`', $name)
            );
        }
        return $object;
    }

    /**
     * @inheritdoc
     */
    public function getAreaScopes()
    {
        return ['*'];
    }


    /**
     * Shutdown the program
     */
    public function __destruct()
    {
        Hooks::doAction('destruct');
    }
}
