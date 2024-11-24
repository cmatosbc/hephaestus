<?php

namespace Hephaestus\Tests;

use PHPUnit\Framework\TestCase;
use Hephaestus\EnhancedException;

class TestEnhancedException extends EnhancedException {}
class SpecificException extends \Exception {}

class EnhancedExceptionTest extends TestCase
{
    public function testStateManagement()
    {
        $exception = new TestEnhancedException("Test exception");
        
        // Test saving and retrieving state
        $exception->saveState(['key' => 'value'], 'test_state');
        $this->assertEquals(['key' => 'value'], $exception->getState('test_state'));
        
        // Test saving multiple states
        $exception->saveState(['data' => 123], 'numeric_state')
                 ->saveState(['flag' => true], 'boolean_state');
        
        $allStates = $exception->getAllStates();
        $this->assertCount(3, $allStates);
        $this->assertEquals(['data' => 123], $allStates['numeric_state']['state']);
        $this->assertEquals(['flag' => true], $allStates['boolean_state']['state']);
    }

    public function testExceptionHistory()
    {
        $previousException = new \RuntimeException("Previous error");
        $exception = new TestEnhancedException("Main error", 0, $previousException);
        
        // Test previous exception is automatically added to history
        $this->assertCount(1, $exception->getExceptionHistory());
        $this->assertSame($previousException, $exception->getLastException());
        
        // Test adding more exceptions
        $additionalException = new SpecificException("Additional error");
        $exception->addToHistory($additionalException);
        
        $this->assertCount(2, $exception->getExceptionHistory());
        $this->assertSame($additionalException, $exception->getLastException());
    }

    public function testExceptionTypeChecking()
    {
        $exception = new TestEnhancedException("Test exception");
        
        $runtimeException = new \RuntimeException("Runtime error");
        $specificException = new SpecificException("Specific error");
        
        $exception->addToHistory($runtimeException)
                 ->addToHistory($specificException);
        
        // Test hasExceptionOfType
        $this->assertTrue($exception->hasExceptionOfType(\RuntimeException::class));
        $this->assertTrue($exception->hasExceptionOfType(SpecificException::class));
        $this->assertFalse($exception->hasExceptionOfType(\LogicException::class));
        
        // Test getExceptionsOfType
        $runtimeExceptions = $exception->getExceptionsOfType(\RuntimeException::class);
        $this->assertCount(1, $runtimeExceptions);
        $this->assertInstanceOf(\RuntimeException::class, $runtimeExceptions[0]);
    }

    public function testClearHistory()
    {
        $exception = new TestEnhancedException("Test exception");
        
        // Add some state and exceptions
        $exception->saveState(['data' => 'test'], 'test_state')
                 ->addToHistory(new \RuntimeException("Error"));
        
        // Verify data is present
        $this->assertCount(1, $exception->getAllStates());
        $this->assertCount(1, $exception->getExceptionHistory());
        
        // Clear history
        $exception->clearHistory();
        
        // Verify data is cleared
        $this->assertCount(0, $exception->getAllStates());
        $this->assertCount(0, $exception->getExceptionHistory());
    }

    public function testComplexStateTracking()
    {
        $exception = new TestEnhancedException("Test exception");
        
        // Test with different data types
        $exception->saveState(42, 'integer')
                 ->saveState(3.14, 'float')
                 ->saveState(['nested' => ['data' => true]], 'array')
                 ->saveState(new \stdClass(), 'object');
        
        $allStates = $exception->getAllStates();
        
        $this->assertIsInt($allStates['integer']['state']);
        $this->assertIsFloat($allStates['float']['state']);
        $this->assertIsArray($allStates['array']['state']);
        $this->assertIsObject($allStates['object']['state']);
    }

    public function testExceptionChaining()
    {
        $level1 = new \RuntimeException("Level 1 error");
        $level2 = new SpecificException("Level 2 error");
        $level3 = new \LogicException("Level 3 error");
        
        $exception = new TestEnhancedException("Main error");
        $exception->addToHistory($level1)
                 ->addToHistory($level2)
                 ->addToHistory($level3);
        
        $history = $exception->getExceptionHistory();
        
        // Test exception order
        $this->assertCount(3, $history);
        $this->assertSame($level1, $history[0]);
        $this->assertSame($level2, $history[1]);
        $this->assertSame($level3, $history[2]);
        
        // Test last exception
        $this->assertSame($level3, $exception->getLastException());
    }
}
