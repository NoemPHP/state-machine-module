<?php

namespace Noem\StateMachineModule\Attribute;

interface StateMachineTag
{
    public const TRANSITION = 'state-machine.transition';
    public const STATE = 'state-machine.state';
    public const ACTION = 'state-machine.action';
}
