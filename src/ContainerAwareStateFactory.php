<?php

declare(strict_types=1);

namespace Noem\StateMachineModule;

use Noem\Container\Container;
use Noem\State\State\HierarchicalState;
use Noem\State\State\ParallelState;
use Noem\State\StateInterface;
use Noem\StateMachineModule\Attribute\State;

class ContainerAwareStateFactory
{

    /**
     * @param Container $container
     * @param string|null $parent
     *
     * @return StateInterface[]
     */
    public static function createChildrenOf(Container $container, string $machine, ?string $parent = null): array
    {
        $result = [];
        $withAttribute = $container->getIdsWithAttribute(
            State::class,
            fn(State $s) => (!$parent || $s->parent === $parent) && $s->machine === $machine
        );
        array_walk(
            $withAttribute,
            function (string $serviceId) use ($container, $machine, &$result) {
                $attributes = $container->getAttributesOfId($serviceId, State::class);
                $stateAttr = reset($attributes);
                assert($stateAttr instanceof State);
                $result[$stateAttr->name] = self::createStateFromAttribute($container, $stateAttr);
            }
        );

        return $result;
    }

    public static function createStateFromAttribute(
        Container $container,
        State $stateAttr
    ): StateInterface {
        $children = $container->getIdsWithAttribute(
            State::class,
            fn(State $maybeChild) => $stateAttr->name === $maybeChild->parent
                && $stateAttr->machine === $maybeChild->machine
        );
        $id = $stateAttr->name;
        if (empty($children)) {
            return new HierarchicalState($id);
        }
        $children = self::createChildrenOf($container, $stateAttr->machine, $stateAttr->name);
        $state = $stateAttr->parallel
            ? new ParallelState($id, null, ...$children)
            : new HierarchicalState($id, null, ...$children);
        array_walk($children, fn(HierarchicalState $c) => $c->setParent($state));

        return $state;
    }
}
