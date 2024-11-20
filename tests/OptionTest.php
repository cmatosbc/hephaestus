<?php

namespace Hephaestus\Tests;

use PHPUnit\Framework\TestCase;
use Hephaestus\Option;
use Hephaestus\Some;
use Hephaestus\None;
use Hephaestus\SomeNoneException;
use function Hephaestus\Some as some;
use function Hephaestus\None as none;

class OptionTest extends TestCase
{
    public function testSomeCreation()
    {
        $some = some(42);
        $this->assertTrue($some->isSome());
        $this->assertFalse($some->isNone());
        $this->assertEquals(42, $some->unwrap());
    }

    public function testNoneCreation()
    {
        $none = none();
        $this->assertTrue($none->isNone());
        $this->assertFalse($none->isSome());
    }

    public function testNoneUnwrapThrowsException()
    {
        $this->expectException(SomeNoneException::class);
        none()->unwrap();
    }

    public function testSomeGetOrElse()
    {
        $some = some(42);
        $this->assertEquals(42, $some->getOrElse(0));
    }

    public function testNoneGetOrElse()
    {
        $none = none();
        $this->assertEquals(0, $none->getOrElse(0));
    }

    public function testSomeMap()
    {
        $some = some(2);
        $doubled = $some->map(fn($x) => $x * 2);
        $this->assertTrue($doubled->isSome());
        $this->assertEquals(4, $doubled->unwrap());
    }

    public function testNoneMap()
    {
        $none = none();
        $doubled = $none->map(fn($x) => $x * 2);
        $this->assertTrue($doubled->isNone());
    }

    public function testSomeFilter()
    {
        $some = some(42);
        $filtered1 = $some->filter(fn($x) => $x > 40);
        $filtered2 = $some->filter(fn($x) => $x < 40);
        
        $this->assertTrue($filtered1->isSome());
        $this->assertTrue($filtered2->isNone());
    }

    public function testNoneFilter()
    {
        $none = none();
        $filtered = $none->filter(fn($x) => true);
        $this->assertTrue($filtered->isNone());
    }

    public function testSomeMatch()
    {
        $some = some(42);
        $result = $some->match(
            fn($x) => "Value is $x",
            fn() => "No value"
        );
        $this->assertEquals("Value is 42", $result);
    }

    public function testNoneMatch()
    {
        $none = none();
        $result = $none->match(
            fn($x) => "Value is $x",
            fn() => "No value"
        );
        $this->assertEquals("No value", $result);
    }

    public function testChaining()
    {
        $result = some(['age' => 25])
            ->filter(fn($user) => $user['age'] >= 21)
            ->map(fn($user) => $user['age'])
            ->getOrElse(0);
        
        $this->assertEquals(25, $result);

        $result = some(['age' => 18])
            ->filter(fn($user) => $user['age'] >= 21)
            ->map(fn($user) => $user['age'])
            ->getOrElse(0);
        
        $this->assertEquals(0, $result);
    }
}
