<?php

namespace Hephaestus;

/**
 * Class representing the absence of a value.
 */
class None extends Option
{
    /**
     * {@inheritdoc}
     */
    public function isSome(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isNone(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws SomeNoneException Always throws an exception when called on None.
     */
    public function unwrap()
    {
        throw new SomeNoneException("Called unwrap on a None value");
    }

    public function getOrElse($default)
    {
        return $default;
    }

    public function map(callable $fn): Option
    {
        return $this;
    }

    public function filter(callable $predicate): Option
    {
        return $this;
    }

    public function match(callable $some, callable $none)
    {
        return $none();
    }
}
