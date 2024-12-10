<?php

namespace Hephaestus;

/**
 * Abstract base class representing an optional value.
 * Subclasses include Some (for present values) and None (for absent values).
 */
abstract class Option
{
    /**
     * Creates an instance representing a present value.
     *
     * @param mixed $value The value to be wrapped in a Some instance.
     * @return Some The Some instance wrapping the provided value.
     */
    public static function some($value): Some
    {
        return new Some($value);
    }

    /**
     * Creates an instance representing the absence of a value.
     *
     * @return None The None instance.
     */
    public static function none(): None
    {
        return new None();
    }

    /**
     * Checks if the instance represents a present value.
     *
     * @return bool True if the instance is Some, false if None.
     */
    abstract public function isSome(): bool;

    /**
     * Checks if the instance represents an absent value.
     *
     * @return bool True if the instance is None, false if Some.
     */
    abstract public function isNone(): bool;

    /**
     * Unwraps the contained value if present, or throws an exception if absent.
     *
     * @return mixed The wrapped value.
     * @throws SomeNoneException If called on a None instance.
     */
    abstract public function unwrap();

    /**
     * Returns the contained value or a default.
     *
     * @param mixed $default The default value to return if None
     * @return mixed The contained value or the default
     */
    abstract public function getOrElse($default);

    /**
     * Maps an Option<T> to Option<U> by applying a function to the contained value.
     *
     * @param callable $fn Function to apply to the contained value
     * @return Option New Option containing the result of the function
     */
    abstract public function map(callable $fn): Option;

    /**
     * Returns None if the option is None, otherwise calls $predicate with the
     * wrapped value and returns Some(t) if predicate returns true, None otherwise.
     *
     * @param callable $predicate Function that returns bool
     * @return Option Same Option if predicate returns true, None otherwise
     */
    abstract public function filter(callable $predicate): Option;

    /**
     * Pattern matching for options.
     *
     * @param callable $some Function to call if Some
     * @param callable $none Function to call if None
     * @return mixed Result of calling either function
     */
    abstract public function match(callable $some, callable $none);
}
