<?php

namespace Hephaestus\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Initialize Hephaestus exception patterns file')
            ->setHelp('This command generates a exceptions.json file with all available exception classes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Initializing Hephaestus Exception Patterns');

        // Get all declared classes
        $classes = get_declared_classes();
        
        // Filter for exception classes
        $exceptionClasses = array_filter($classes, function($class) {
            return is_subclass_of($class, \Exception::class);
        });

        // Create patterns array
        $patterns = [];
        foreach ($exceptionClasses as $class) {
            $reflection = new \ReflectionClass($class);
            $shortName = $reflection->getShortName();
            
            // Create a human-readable message from the class name
            $message = trim(preg_replace('/(?<!\ )[A-Z]/', ' $0', $shortName));
            if (str_ends_with($message, ' Exception')) {
                $message = substr($message, 0, -10);
            }

            // Get the class docblock if available
            $description = '';
            if ($docComment = $reflection->getDocComment()) {
                // Remove the comment markers and asterisks
                $description = trim(preg_replace('/^\s*\*\s|\/\*\*|\*\/$|\s*$|^$/m', '', $docComment));
                // If multiple lines, take only the first one as description
                if (strpos($description, "\n") !== false) {
                    $description = substr($description, 0, strpos($description, "\n"));
                }
            }
            
            $patterns[$class] = [
                'message' => $message,
                'description' => $description ?: "Represents a {$message} error condition."
            ];
        }

        // Sort patterns by class name
        ksort($patterns);

        // Create the exceptions.json file
        $jsonContent = json_encode($patterns, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $targetFile = getcwd() . '/exceptions.json';

        if (file_exists($targetFile)) {
            if (!$io->confirm('exceptions.json already exists. Do you want to overwrite it?', false)) {
                $io->warning('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        file_put_contents($targetFile, $jsonContent);

        $io->success([
            'exceptions.json has been generated successfully.',
            'Found ' . count($patterns) . ' exception classes.',
            'You can now edit the messages and descriptions in exceptions.json'
        ]);

        // Show example of the generated format
        $io->section('Generated Format Example');
        $firstException = array_key_first($patterns);
        $example = [
            $firstException => $patterns[$firstException]
        ];
        $io->text(json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }
}
