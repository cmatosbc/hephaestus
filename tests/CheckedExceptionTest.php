<?php

namespace Hephaestus\Tests;

use PHPUnit\Framework\TestCase;
use Hephaestus\CheckedException;
use function Hephaestus\withCheckedExceptionHandling;

class TestCheckedException extends CheckedException {}

class CheckedExceptionTest extends TestCase
{
    public function throwingFunction()
    {
        throw new TestCheckedException("Test exception");
    }

    public function successFunction()
    {
        return "success";
    }

    public function testWithCheckedExceptionHandlingClosure()
    {
        // Test with throwing closure
        $result = withCheckedExceptionHandling(function() {
            throw new TestCheckedException("Test exception");
        });
        $this->assertNull($result);

        // Test with successful closure
        $result = withCheckedExceptionHandling(function() {
            return "success";
        });
        $this->assertEquals("success", $result);
    }

    public function testWithCheckedExceptionHandlingNamedFunction()
    {
        // Test with throwing function
        $result = withCheckedExceptionHandling([$this, 'throwingFunction']);
        $this->assertNull($result);

        // Test with successful function
        $result = withCheckedExceptionHandling([$this, 'successFunction']);
        $this->assertEquals("success", $result);
    }

    public function testWithCheckedExceptionHandlingArgs()
    {
        $result = withCheckedExceptionHandling(
            function($arg) { return $arg * 2; },
            21
        );
        $this->assertEquals(42, $result);
    }

    public function testWithCheckedExceptionHandlingObject()
    {
        $obj = new class {
            public function throwingMethod() {
                throw new TestCheckedException("Test exception");
            }

            public function successMethod($value) {
                return $value;
            }
        };

        // Test with throwing method
        $result = withCheckedExceptionHandling([$obj, 'throwingMethod']);
        $this->assertNull($result);

        // Test with successful method
        $result = withCheckedExceptionHandling([$obj, 'successMethod'], 42);
        $this->assertEquals(42, $result);
    }

    public function testWithCheckedExceptionHandlingOtherExceptions()
    {
        // Regular exceptions should not be caught
        $this->expectException(\RuntimeException::class);
        
        withCheckedExceptionHandling(function() {
            throw new \RuntimeException("Regular exception");
        });
    }
}
