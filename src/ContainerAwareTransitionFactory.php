<?php

declare(strict_types=1);


namespace Noem\StateMachineModule;


use Noem\Container\Container;
use Noem\State\State\StateDefinitions;
use Noem\State\Transition\TransitionProvider;
use Noem\StateMachineModule\Attribute\Transition;

class ContainerAwareTransitionFactory
{

    public static function createTransitionProviderForMachine(
        string $machine,
        StateDefinitions $definitions,
        Container $container,
    ): TransitionProvider {
        $provider = new TransitionProvider($definitions);
        self::registerTransitionsForMachine($machine, $provider, $container);
        return $provider;
    }

    public static function registerTransitionsForMachine(
        string $machine,
        TransitionProvider $provider,
        Container $container
    ): void {
        $transitionDefs = $container->getIdsWithAttribute(
            Transition::class,
            fn(Transition $p) => $p->machine === $machine
        );
        array_walk(
            $transitionDefs,
            function (string $id) use ($container, $provider) {
                $guard = $container->get($id);
                $attributes = $container->getAttributesOfId($id, Transition::class);
                /**
                 * @var TransitionAttr $transitionAttr
                 */
                $transitionAttr = reset($attributes);
                assert($transitionAttr instanceof Transition);
                $provider->registerTransition(
                    $transitionAttr->from,
                    $transitionAttr->to,
                    $guard
                );
            },
        );
    }
}
