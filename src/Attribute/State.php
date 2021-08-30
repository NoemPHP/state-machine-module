<?php

declare(strict_types=1);


namespace Noem\StateMachineModule\Attribute;


use Attribute;
use Nette\Schema\Expect;
use Nette\Schema\Processor;

/**
 * @property string name
 * @property string parallel
 * @property string parent
 * @property string machine
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
class State
{
    private array $props;

    /**
     */
    public function __construct(...$props)
    {
        $props = array_merge([
                                 'parent' => null,
                                 'machine' => 'default',
                             ], $props);
        $schema = Expect::structure([
                                        'machine' => Expect::string()->required(),
                                        'name' => Expect::string()->required(),
                                        'parent' => Expect::string()->nullable(),
                                        'parallel' => Expect::bool(),
                                    ]);
        $processor = new Processor();
        $processor->process($schema, $props);
        $this->props = $props;
    }

    public function __get(string $key)
    {
        if (!array_key_exists($key, $this->props)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Property "%s" does not exist on %s',
                    $key,
                    self::class
                )
            );
        }
        return $this->props[$key];
    }

    public function toArray(): array
    {
        return $this->props;
    }
}
