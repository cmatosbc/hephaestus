<?php

namespace Hephaestus\Tests;

use PHPUnit\Framework\TestCase;
use function Hephaestus\withRetryBeforeFailing;
use function Hephaestus\Some;
use function Hephaestus\None;
use Hephaestus\Option;

class RetryTest extends TestCase
{
    public function testSuccessfulOperationWithoutRetry()
    {
        $retrier = withRetryBeforeFailing(3);
        $result = $retrier(function() {
            return "success";
        });
        
        $this->assertEquals("success", $result);
    }

    public function testSuccessAfterSomeRetries()
    {
        $attempts = 0;
        $retrier = withRetryBeforeFailing(3);
        
        $result = $retrier(function() use (&$attempts) {
            $attempts++;
            if ($attempts < 2) {
                throw new \Exception("Temporary failure");
            }
            return "success after retry";
        });
        
        $this->assertEquals("success after retry", $result);
        $this->assertEquals(2, $attempts);
    }

    public function testFailureAfterAllRetries()
    {
        $attempts = 0;
        $retrier = withRetryBeforeFailing(3);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("All 3 attempts failed");
        
        $retrier(function() use (&$attempts) {
            $attempts++;
            throw new \Exception("Persistent failure");
        });
    }

    public function testCustomRetryCount()
    {
        $attempts = 0;
        $retrier = withRetryBeforeFailing(5);
        
        $result = $retrier(function() use (&$attempts) {
            $attempts++;
            if ($attempts < 4) {
                throw new \Exception("Temporary failure");
            }
            return "success after multiple retries";
        });
        
        $this->assertEquals("success after multiple retries", $result);
        $this->assertEquals(4, $attempts);
    }

    public function testIntegrationWithOption()
    {
        $attempts = 0;
        $retrier = withRetryBeforeFailing(3);
        
        $fetchData = function() use ($retrier, &$attempts): \Hephaestus\Option {
            try {
                $result = $retrier(function() use (&$attempts) {
                    $attempts++;
                    if ($attempts < 2) {
                        throw new \Exception("Temporary failure");
                    }
                    return ["value" => 42];
                });
                return Some($result);
            } catch (\Exception $e) {
                return None();
            }
        };
        
        $result = $fetchData()
            ->map(fn($data) => $data['value'])
            ->getOrElse(0);
            
        $this->assertEquals(42, $result);
        $this->assertEquals(2, $attempts);
    }

    public function testNestedRetries()
    {
        $outerAttempts = 0;
        $innerAttempts = 0;
        
        $outerRetrier = withRetryBeforeFailing(2);
        $innerRetrier = withRetryBeforeFailing(2);
        
        $result = $outerRetrier(function() use ($innerRetrier, &$outerAttempts, &$innerAttempts) {
            $outerAttempts++;
            return $innerRetrier(function() use (&$innerAttempts) {
                $innerAttempts++;
                if ($innerAttempts < 2) {
                    throw new \Exception("Inner operation failed");
                }
                return "nested success";
            });
        });
        
        $this->assertEquals("nested success", $result);
        $this->assertEquals(1, $outerAttempts);
        $this->assertEquals(2, $innerAttempts);
    }

    public function testExceptionChaining()
    {
        $retrier = withRetryBeforeFailing(2);
        
        try {
            $retrier(function() {
                throw new \RuntimeException("Original error");
            });
            $this->fail("Expected exception was not thrown");
        } catch (\Exception $e) {
            $this->assertStringContainsString("All 2 attempts failed", $e->getMessage());
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());
            $this->assertEquals("Original error", $e->getPrevious()->getMessage());
        }
    }
}
