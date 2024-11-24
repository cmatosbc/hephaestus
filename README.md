# Hephaestus

God-like error handling library for modern PHP programmers. Hephaestus provides a comprehensive set of tools for elegant error handling, combining functional programming concepts with robust exception management.

## Features

- **Option Type**: Rust-inspired `Some/None` type for safe handling of nullable values
- **Enhanced Exceptions**: Advanced error handling with state tracking and exception history
- **Retry Mechanism**: Built-in retry capabilities for transient failures
- **Symfony Integration**: Seamless integration with Symfony framework (optional)
- **Type Safety**: Full PHP 8.1+ type system support

## Requirements

- PHP 8.1 or higher
- Symfony 7.1 or higher (optional, for bundle integration)

## Installation

### Basic Installation

```bash
composer require cmatosbc/hephaestus
```

### Symfony Bundle Installation

1. Install the package as shown above
2. Register the bundle in `config/bundles.php`:
```php
return [
    // ...
    Hephaestus\Bundle\HephaestusBundle::class => ['all' => true],
];
```

3. Configure the bundle in `config/packages/hephaestus.yaml`:
```yaml
hephaestus:
    max_retries: 3
    retry_delay: 1
    logging:
        enabled: true
        channel: 'hephaestus'
```

## Core Components

### Option Type

The Option type provides a safe way to handle potentially null values:

```php
use function Hephaestus\Some;
use function Hephaestus\None;

// Basic usage
$user = Some(['name' => 'Zeus']);
$name = $user->map(fn($u) => $u['name'])
            ->getOrElse('Anonymous'); // Returns "Zeus"

// Pattern matching
$greeting = $user->match(
    some: fn($u) => "Welcome, {$u['name']}!",
    none: fn() => "Welcome, stranger!"
);

// Chaining operations
$drinking_age = Some(['name' => 'Dionysus', 'age' => 21])
    ->filter(fn($u) => $u['age'] >= 21)
    ->map(fn($u) => "{$u['name']} can drink!")
    ->getOrElse("Not old enough");
```

### Enhanced Exception Handling

The `EnhancedException` class provides state tracking and exception history management:

```php
use Hephaestus\EnhancedException;

class DatabaseException extends EnhancedException {}

try {
    throw new DatabaseException(
        "Query failed",
        0,
        new \PDOException("Connection lost")
    );
} catch (DatabaseException $e) {
    // Track exception history
    $history = $e->getExceptionHistory();
    $lastError = $e->getLastException();
    
    // Add context
    $e->saveState(['query' => 'SELECT * FROM users'])
      ->addToHistory(new \Exception("Additional context"));
    
    // Type-specific error handling
    if ($e->hasExceptionOfType(\PDOException::class)) {
        $pdoErrors = $e->getExceptionsOfType(\PDOException::class);
    }
}
```

### Retry Mechanism

Built-in retry capabilities for handling transient failures:

```php
use function Hephaestus\withRetryBeforeFailing;

// Retry an operation up to 3 times
$result = withRetryBeforeFailing(3)(function() {
    return file_get_contents('https://api.example.com/data');
});

// Combine with Option type for safer error handling
function fetchDataSafely($url): Option {
    return withRetryBeforeFailing(3)(function() use ($url) {
        $response = file_get_contents($url);
        return $response === false ? None() : Some(json_decode($response, true));
    });
}

$data = fetchDataSafely('https://api.example.com/data')
    ->map(fn($d) => $d['value'])
    ->getOrElse('default');
```

### Pattern-Matched Exception Handling

Hephaestus provides a powerful pattern-matched exception handling system that allows you to define custom error messages and descriptions for different exception types.

#### Setting Up Exception Patterns

First, generate the exception patterns file using the `hephaestus` command:

```bash
# Generate exceptions.json with all available exception classes
./vendor/bin/hephaestus init
```

This will create an `exceptions.json` file in your project root with the following structure:

```json
{
    "RuntimeException": {
        "message": "Runtime Error",
        "description": "An error that can only be detected during program execution."
    },
    "InvalidArgumentException": {
        "message": "Invalid Input",
        "description": "The provided argument is not valid for this operation."
    }
}
```

You can customize the messages and descriptions in this file to match your application's needs.

#### Using Pattern-Matched Exception Handling

The `withMatchedExceptions()` function provides a clean way to handle exceptions using the patterns defined in your `exceptions.json` file:

```php
use function Hephaestus\withMatchedExceptions;

// Basic usage
$result = withMatchedExceptions(
    fn() => $connection->connect()
);

// With a custom patterns file
$result = withMatchedExceptions(
    fn() => $service->riskyOperation(),
    __DIR__ . '/custom/patterns.json'
);

// With a custom default message
$result = withMatchedExceptions(
    fn() => $api->request(),
    null,
    'API Error'
);

// Using array callable
$result = withMatchedExceptions(
    [$service, 'method']
);
```

When an exception occurs, it will be matched against the patterns in your `exceptions.json` file, and a formatted error message will be returned:

```php
// If a DatabaseException occurs:
"Database Error: Failed to connect to localhost"

Description: A database connection or operation error occurred. Check your database credentials and ensure the database server is running.
```

#### Command Line Tool

The `hephaestus` command line tool helps you manage your exception patterns:

```bash
# Generate patterns file
./vendor/bin/hephaestus init

# View available commands
./vendor/bin/hephaestus list
```

#### Command Options

- `init`: Generates the `exceptions.json` file
  - Scans your project for exception classes
  - Creates human-readable messages from class names
  - Extracts descriptions from class docblocks when available
  - Prompts before overwriting existing file

#### Customizing Exception Patterns

You can customize your `exceptions.json` file to provide more specific messages and descriptions:

```json
{
    "App\\Exceptions\\DatabaseException": {
        "message": "Database Connection Failed",
        "description": "Unable to establish a connection to the database. Please check your database configuration and ensure the database server is running."
    },
    "App\\Exceptions\\ValidationException": {
        "message": "Invalid Input Data",
        "description": "The provided data failed validation. Please check your input and try again."
    }
}
```

#### Error Message Format

The error messages returned by `withMatchedExceptions()` follow this format:

```
[Custom Message]: [Exception Message]

Description: [Custom Description]
```

For unmatched exceptions, a default message is used:

```
Unexpected error: [Exception Message]

Description: A general error has occurred.
```

## Symfony Integration

### Option Factory Service

The bundle provides an `OptionFactory` service with built-in retry capabilities:

```php
use Hephaestus\Bundle\Service\OptionFactory;

class UserService
{
    public function __construct(private OptionFactory $optionFactory)
    {}

    public function findUser(int $id): Option
    {
        return $this->optionFactory->fromCallable(
            fn() => $this->userRepository->find($id)
        );
    }
}
```

### HTTP-Aware Exceptions

The `SymfonyEnhancedException` class provides HTTP-specific error handling:

```php
use Hephaestus\Bundle\Exception\SymfonyEnhancedException;

class UserNotFoundException extends SymfonyEnhancedException
{
    public function __construct(int $userId)
    {
        parent::__construct(
            "User not found",
            Response::HTTP_NOT_FOUND
        );
        $this->saveState(['user_id' => $userId]);
    }
}

class UserController
{
    public function show(int $id): Response
    {
        return $this->optionFactory
            ->fromCallable(fn() => $this->userRepository->find($id))
            ->match(
                some: fn($user) => $this->json($user),
                none: fn() => throw new UserNotFoundException($id)
            );
    }
}
```

## Key Benefits

- **Type Safety**: Leverage PHP 8.1+ type system for safer code
- **Functional Approach**: Chain operations with map, filter, and match
- **Explicit Error Handling**: Make potential failures visible in method signatures
- **State Tracking**: Capture and maintain error context and history
- **Resilient Operations**: Built-in retry mechanism for transient failures
- **Framework Integration**: Seamless Symfony integration when needed

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This project is licensed under the GNU General Public License v3.0 or later - see the [LICENSE](LICENSE) file for details. This means you are free to use, modify, and distribute this software, but any modifications must also be released under the GPL-3.0-or-later license.
