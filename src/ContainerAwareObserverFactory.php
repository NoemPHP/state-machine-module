<?php

declare(strict_types=1);


namespace Noem\StateMachineModule;


use Noem\Container\Container;
use Noem\State\EventManager;
use Noem\State\Observer\StateMachineObserver;
use Noem\StateMachineModule\Attribute\Action;
use Noem\StateMachineModule\Attribute\OnEntry;
use Noem\StateMachineModule\Attribute\OnExit;

class ContainerAwareObserverFactory
{
    public static function createObserverForMachine(string $machine, Container $container): StateMachineObserver
    {
        $observer = new EventManager();
        self::registerOnEntryCallbacksForMachine($machine, $observer, $container);
        self::registerOnExitCallbacksForMachine($machine, $observer, $container);
        self::registerActionCallbacksForMachine($machine, $observer, $container);
        return $observer;
    }

    private static function registerOnEntryCallbacksForMachine(
        string $machine,
        EventManager $eventManager,
        Container $container
    ) {
        $onEntryDefs = $container->getIdsWithAttribute(
            OnEntry::class,
            fn(OnEntry $p) => $p->machine === $machine
        );
        array_walk(
            $onEntryDefs,
            function (string $id) use ($container, $eventManager) {
                $callback = $container->get($id);
                $attributes = $container->getAttributesOfId($id, OnEntry::class);
                /**
                 * @var OnEntry $onEntryAttr
                 */
                $onEntryAttr = reset($attributes);
                assert($onEntryAttr instanceof OnEntry);
                $eventManager->addEnterStateHandler(
                    $onEntryAttr->state,
                    $callback
                );
            },
        );
    }

    private static function registerOnExitCallbacksForMachine(
        string $machine,
        EventManager $eventManager,
        Container $container
    ) {
        $onEntryDefs = $container->getIdsWithAttribute(
            OnExit::class,
            fn(OnExit $p) => $p->machine === $machine
        );
        array_walk(
            $onEntryDefs,
            function (string $id) use ($container, $eventManager) {
                $callback = $container->get($id);
                $attributes = $container->getAttributesOfId($id, OnExit::class);
                /**
                 * @var OnExit $onEntryAttr
                 */
                $onEntryAttr = reset($attributes);
                assert($onEntryAttr instanceof OnExit);
                $eventManager->addExitStateHandler(
                    $onEntryAttr->state,
                    $callback
                );
            },
        );
    }

    private static function registerActionCallbacksForMachine(
        string $machine,
        EventManager $eventManager,
        Container $container
    ) {
        $onEntryDefs = $container->getIdsWithAttribute(
            Action::class,
            fn(Action $p) => $p->machine === $machine
        );
        array_walk(
            $onEntryDefs,
            function (string $id) use ($container, $eventManager) {
                $callback = $container->get($id);
                $attributes = $container->getAttributesOfId($id, Action::class);
                /**
                 * @var Action $onEntryAttr
                 */
                $onEntryAttr = reset($attributes);
                assert($onEntryAttr instanceof Action);
                $eventManager->addActionHandler(
                    $onEntryAttr->state,
                    $callback
                );
            },
        );
    }
}
