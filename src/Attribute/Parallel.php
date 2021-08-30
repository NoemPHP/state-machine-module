<?php

declare(strict_types=1);


namespace Noem\StateMachineModule\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
class Parallel
{

}
