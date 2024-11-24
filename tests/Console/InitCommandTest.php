<?php

namespace Hephaestus\Tests\Console;

use PHPUnit\Framework\TestCase;
use Hephaestus\Console\InitCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

class InitCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary directory for testing
        $this->tempDir = sys_get_temp_dir() . '/hephaestus_test_' . uniqid();
        mkdir($this->tempDir);
        chdir($this->tempDir);

        $application = new Application();
        $application->add(new InitCommand());
        $command = $application->find('init');
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (file_exists($this->tempDir . '/exceptions.json')) {
            unlink($this->tempDir . '/exceptions.json');
        }
        rmdir($this->tempDir);
        
        parent::tearDown();
    }

    public function testExecute(): void
    {
        // Execute the command
        $this->commandTester->execute([]);

        // Assert command was successful
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        // Assert file was created
        $this->assertFileExists($this->tempDir . '/exceptions.json');

        // Load and validate JSON content
        $content = json_decode(file_get_contents($this->tempDir . '/exceptions.json'), true);
        $this->assertIsArray($content);
        
        // Assert JSON structure
        $firstException = array_key_first($content);
        $this->assertArrayHasKey('message', $content[$firstException]);
        $this->assertArrayHasKey('description', $content[$firstException]);
        $this->assertIsString($content[$firstException]['message']);
        $this->assertIsString($content[$firstException]['description']);
    }

    public function testExecuteWithExistingFile(): void
    {
        // Create an existing file
        file_put_contents($this->tempDir . '/exceptions.json', '{}');

        // Execute with "no" to overwrite
        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([]);

        // Assert original file was not modified
        $this->assertEquals('{}', file_get_contents($this->tempDir . '/exceptions.json'));

        // Execute with "yes" to overwrite
        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([]);

        // Assert file was overwritten with new content
        $content = json_decode(file_get_contents($this->tempDir . '/exceptions.json'), true);
        $this->assertNotEquals('{}', json_encode($content));
    }

    public function testGeneratedPatternsFormat(): void
    {
        $this->commandTester->execute([]);
        $content = json_decode(file_get_contents($this->tempDir . '/exceptions.json'), true);

        foreach ($content as $class => $data) {
            // Assert class exists and is an Exception
            $this->assertTrue(class_exists($class));
            $this->assertTrue(is_subclass_of($class, \Exception::class));

            // Assert pattern structure
            $this->assertArrayHasKey('message', $data);
            $this->assertArrayHasKey('description', $data);
            $this->assertNotEmpty($data['message']);
            $this->assertNotEmpty($data['description']);
        }
    }
}
