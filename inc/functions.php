<?php

declare(strict_types=1);

use Noem\StateMachineModule\ContainerAwareStateFactory;

/**
 * Helper function to construct state graphs within service factory functions based on Attributes
 * @return callable
 */
function state(): callable
{
    static $factory;
    if (!$factory) {
        $factory = Closure::fromCallable([ContainerAwareStateFactory::class, 'createStateFromAttribute']);
    }
    return $factory;
}
