<?php

declare(strict_types=1);

use Noem\Container\Attribute\Description;
use Noem\Container\Attribute\Id;
use Noem\Container\Attribute\Tag;
use Noem\Container\Container;
use Noem\State\InMemoryStateStorage;
use Noem\State\Loader\ArrayLoader;
use Noem\State\Loader\LoaderInterface;
use Noem\State\State\StateDefinitions;
use Noem\State\StateMachine;
use Noem\State\StateMachineInterface;
use Noem\State\StateStorageInterface;
use Noem\State\Transition\AggregateTransitionProvider;
use Noem\StateMachineModule\Attribute\Observer;
use Noem\StateMachineModule\Attribute\TransitionProvider as TransitionProviderAttr;
use Noem\StateMachineModule\ContainerAwareObserverFactory;
use Noem\StateMachineModule\ContainerAwareStateFactory;
use Noem\StateMachineModule\ContainerAwareTransitionFactory;

return [
    'state-machine.observer' =>
        #[Observer]
        fn(Container $c) => ContainerAwareObserverFactory::createObserverForMachine('default', $c),
    'state-machine.graph' => function (Container $c) {
        $states = ContainerAwareStateFactory::createChildrenOf($c, 'default', null);
        return new StateDefinitions($states);
    },
    'state-machine.transitions' =>
        #[TransitionProviderAttr]
        fn(
            Container $c,
            #[Id('state-machine.graph')] StateDefinitions $definitions
        ) => ContainerAwareTransitionFactory::createTransitionProviderForMachine(
            'default',
            $definitions,
            $c
        ),
    'state-machine.psr-14-bridge' =>
        #[Tag('event-listener')]
        fn(#[Id('state-machine')] StateMachineInterface $stateMachine): callable => function (object $event) use (
            $stateMachine
        ) {
            $stateMachine->trigger($event);
        },
    'state-machine-factory' =>

        #[Description("
Returns a function that constructs and returns StateMachine instances based on Container entries and their attributes
    ")]
        fn(Container $c): callable => function (string $machine, StateStorageInterface $store) use ($c) {
            $transitionProviders = $c->getIdsWithAttribute(
                TransitionProviderAttr::class,
                fn(TransitionProviderAttr $p) => $p->stateMachine === $machine
            );
            if (empty($transitionProviders)) {
                throw new OutOfBoundsException(
                    sprintf(
                        'There are no services with the "%s" attribute for state machine "%s"',
                        TransitionProviderAttr::class,
                        $machine
                    ),
                );
            }
            $provider = new AggregateTransitionProvider(...array_map(fn($id) => $c->get($id), $transitionProviders));
            $observers = $c->getIdsWithAttribute(
                Observer::class,
                fn(Observer $p) => $p->stateMachine === $machine
            );
            $observers = array_map(fn($id) => $c->get($id), $observers);
            $machine = new StateMachine($provider, $store);
            /** @noinspection PhpUndefinedVariableInspection WTF? */
            foreach ($observers as $observer) {
                $machine->attach($observer);
            }
            return $machine;
        },
    'state-machine' => function (
        #[Id('state-machine-factory')]
        callable $factory,
        #[Id('state-machine.initial-state')]
        string $initialState,
        #[Id('state-machine.graph')] StateDefinitions $definitions
    ): StateMachine {
        return $factory('default', new InMemoryStateStorage($definitions->get($initialState)));
    }
];
