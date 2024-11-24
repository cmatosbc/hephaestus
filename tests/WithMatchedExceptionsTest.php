<?php

namespace Hephaestus\Tests;

use PHPUnit\Framework\TestCase;
use function Hephaestus\withMatchedExceptions;

class WithMatchedExceptionsTest extends TestCase
{
    private string $tempDir;
    private string $patternsFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary directory and patterns file for testing
        $this->tempDir = sys_get_temp_dir() . '/hephaestus_test_' . uniqid();
        mkdir($this->tempDir);
        $this->patternsFile = $this->tempDir . '/exceptions.json';

        // Create a test patterns file
        $patterns = [
            \RuntimeException::class => [
                'message' => 'Runtime Error',
                'description' => 'An error that can only be detected during program execution.'
            ],
            \InvalidArgumentException::class => [
                'message' => 'Invalid Input',
                'description' => 'The provided argument is not valid for this operation.'
            ]
        ];

        file_put_contents($this->patternsFile, json_encode($patterns));
    }

    protected function tearDown(): void
    {
        unlink($this->patternsFile);
        rmdir($this->tempDir);
        parent::tearDown();
    }

    public function testSuccessfulExecution(): void
    {
        $result = withMatchedExceptions(
            fn() => 'success',
            $this->patternsFile
        );

        $this->assertEquals('success', $result);
    }

    public function testMatchedExceptionHandling(): void
    {
        $result = withMatchedExceptions(
            fn() => throw new \RuntimeException('Something went wrong'),
            $this->patternsFile
        );

        $this->assertStringContainsString('Runtime Error: Something went wrong', $result);
        $this->assertStringContainsString('An error that can only be detected during program execution', $result);
    }

    public function testUnmatchedExceptionHandling(): void
    {
        $result = withMatchedExceptions(
            fn() => throw new \Exception('Unknown error'),
            $this->patternsFile
        );

        $this->assertStringContainsString('Unexpected error: Unknown error', $result);
        $this->assertStringContainsString('A general error has occurred', $result);
    }

    public function testCustomDefaultPattern(): void
    {
        $result = withMatchedExceptions(
            fn() => throw new \Exception('Test error'),
            $this->patternsFile,
            'Custom Error'
        );

        $this->assertStringContainsString('Custom Error: Test error', $result);
    }

    public function testCallableArray(): void
    {
        $obj = new class() {
            public function test() {
                return 'success';
            }
        };

        $result = withMatchedExceptions(
            [$obj, 'test'],
            $this->patternsFile
        );

        $this->assertEquals('success', $result);
    }

    public function testMissingPatternsFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Exception patterns file not found");

        withMatchedExceptions(
            fn() => 'test',
            $this->tempDir . '/nonexistent.json'
        );
    }

    public function testInvalidPatternsFile(): void
    {
        // Create an invalid JSON file
        file_put_contents($this->patternsFile, '{invalid json}');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Invalid exception patterns file");

        withMatchedExceptions(
            fn() => 'test',
            $this->patternsFile
        );
    }

    public function testNestedExceptionHandling(): void
    {
        $result = withMatchedExceptions(
            function() {
                try {
                    throw new \RuntimeException('Inner error');
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException('Outer error: ' . $e->getMessage());
                }
            },
            $this->patternsFile
        );

        $this->assertStringContainsString('Invalid Input: Outer error: Inner error', $result);
        $this->assertStringContainsString('The provided argument is not valid for this operation', $result);
    }
}
