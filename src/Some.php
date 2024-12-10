<?php

namespace Hephaestus;

/**
 * Class representing a present value.
 */
class Some extends Option
{
    /**
     * @var mixed The wrapped value.
     */
    private $value;

    /**
     * Constructor for Some.
     *
     * @param mixed $value The value to be wrapped.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function isSome(): bool
    {
        return true;
    }

    public function isNone(): bool
    {
        return false;
    }

    public function unwrap()
    {
        return $this->value;
    }

    public function getOrElse($default)
    {
        return $this->value;
    }

    public function map(callable $fn): Option
    {
        return new Some($fn($this->value));
    }

    public function filter(callable $predicate): Option
    {
        return $predicate($this->value) ? $this : Option::none();
    }

    public function match(callable $some, callable $none)
    {
        return $some($this->value);
    }
}
