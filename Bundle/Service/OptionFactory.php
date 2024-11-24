<?php

namespace Hephaestus\Bundle\Service;

use Hephaestus\Bundle\Exception\SymfonyEnhancedException;
use Hephaestus\Option;
use Symfony\Component\Form\FormInterface;
use function Hephaestus\Some;
use function Hephaestus\None;

class OptionFactory
{
    private int $maxRetries;
    private int $retryDelay;

    public function __construct(int $maxRetries = 3, int $retryDelay = 1)
    {
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }

    /**
     * Create an Option from a nullable value
     */
    public function fromNullable(mixed $value): Option
    {
        return $value === null ? None() : Some($value);
    }

    /**
     * Create an Option from a Symfony form
     */
    public function fromForm(FormInterface $form): Option
    {
        return $form->isSubmitted() && $form->isValid()
            ? Some($form->getData())
            : None();
    }

    /**
     * Create an Option from an array key
     */
    public function fromArrayKey(array $array, string|int $key): Option
    {
        return array_key_exists($key, $array) ? Some($array[$key]) : None();
    }

    /**
     * Create an Option from a callable that might throw an exception
     */
    public function fromCallable(callable $callable, mixed ...$args): Option
    {
        $attempts = 0;
        $exceptions = [];

        while ($attempts < $this->maxRetries) {
            try {
                return Some($callable(...$args));
            } catch (\Throwable $e) {
                $attempts++;
                $exceptions[] = $e;

                if ($attempts < $this->maxRetries) {
                    sleep($this->retryDelay);
                }
            }
        }

        throw (new SymfonyEnhancedException(
            "Operation failed after {$this->maxRetries} attempts",
            500
        ))->withExceptionHistory($exceptions);
    }
}
