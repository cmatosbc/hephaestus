<?php

namespace Hephaestus\Bundle\Service;

use Hephaestus\Option;
use Symfony\Component\Form\FormInterface;
use function Hephaestus\Some;
use function Hephaestus\None;

class OptionFactory
{
    /**
     * Creates an Option from a nullable value
     */
    public function fromNullable(?mixed $value): Option
    {
        return $value === null ? None() : Some($value);
    }
    
    /**
     * Creates an Option from a Symfony form
     */
    public function fromForm(FormInterface $form): Option
    {
        return $form->isSubmitted() && $form->isValid()
            ? Some($form->getData())
            : None();
    }

    /**
     * Creates an Option from an array key
     */
    public function fromArrayKey(array $array, string|int $key): Option
    {
        return isset($array[$key]) ? Some($array[$key]) : None();
    }

    /**
     * Creates an Option from a callable that might return null
     */
    public function fromCallable(callable $callable, ...$args): Option
    {
        try {
            $result = $callable(...$args);
            return $this->fromNullable($result);
        } catch (\Throwable $e) {
            return None();
        }
    }
}
