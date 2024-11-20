# hephaestus
God-like error handling library for modern PHP programmers.

## Features

- **Option Type (Some/None)**: Rust-inspired Option type for handling nullable values elegantly
- **CheckedException**: Java-inspired checked exceptions for explicit error handling

## Installation

```bash
composer require cmatosbc/hephaestus
```

## Usage

### withCheckedExceptionHandling

The `withCheckedExceptionHandling` function provides a high-order function for handling checked exceptions in a functional style.

```php
use Hephaestus\CheckedException;
use function Hephaestus\withCheckedExceptionHandling;

class ConfigNotFoundException extends CheckedException {}

// Function that may throw a CheckedException
function readConfig(string $path): array {
    if (!file_exists($path)) {
        throw new ConfigNotFoundException("Config file not found: {$path}");
    }
    return ['debug' => true];
}

// Using withCheckedExceptionHandling with a closure
$config = withCheckedExceptionHandling(
    function() {
        return readConfig('/path/to/config.json');
    }
); // Returns null if exception is thrown, prints error message

// Using withCheckedExceptionHandling with a named function
$config = withCheckedExceptionHandling('readConfig', '/path/to/config.json');

// Using withCheckedExceptionHandling with an object method
class ConfigReader {
    public function read(string $path): array {
        return readConfig($path);
    }
}

$reader = new ConfigReader();
$config = withCheckedExceptionHandling([$reader, 'read'], '/path/to/config.json');

// Combining with Option type for more functional style
function readConfigSafe(string $path): Option {
    $result = withCheckedExceptionHandling('readConfig', $path);
    return $result === null ? None() : Some($result);
}

// Usage
$debug_mode = readConfigSafe('/path/to/config.json')
    ->map(fn($config) => $config['debug'])
    ->getOrElse(false);  // Returns false if config file not found
```

### Option Type (Some/None)

The Option type provides a safe way to handle potentially null values, similar to Rust's Option enum.

```php
use Hephaestus\Option;
use function Hephaestus\Some;
use function Hephaestus\None;

// Basic usage
$user = Some(['name' => 'Zeus', 'age' => 1000]);
$noUser = None();

// Safe value extraction with getOrElse
$name = $user->map(fn($u) => $u['name'])
            ->getOrElse('Anonymous'); // Returns "Zeus"

$missingName = $noUser->map(fn($u) => $u['name'])
                     ->getOrElse('Anonymous'); // Returns "Anonymous"

// Chaining operations with map and filter
$drinking_age = Some(['name' => 'Dionysus', 'age' => 21])
    ->filter(fn($u) => $u['age'] >= 21)
    ->map(fn($u) => "{$u['name']} can drink!")
    ->getOrElse("Not old enough");  // Returns "Dionysus can drink!"

$minor = Some(['name' => 'Hebe', 'age' => 16])
    ->filter(fn($u) => $u['age'] >= 21)
    ->map(fn($u) => "{$u['name']} can drink!")
    ->getOrElse("Not old enough");  // Returns "Not old enough"

// Pattern matching
$greeting = $user->match(
    some: fn($u) => "Welcome back, {$u['name']}!",
    none: fn() => "Welcome, stranger!"
); // Returns "Welcome back, Zeus!"

$missingGreeting = $noUser->match(
    some: fn($u) => "Welcome back, {$u['name']}!",
    none: fn() => "Welcome, stranger!"
); // Returns "Welcome, stranger!"

// Error handling with unwrap
try {
    $value = Some(42)->unwrap(); // Returns 42
    $none = None()->unwrap();    // Throws SomeNoneException
} catch (SomeNoneException $e) {
    // Handle the error
}
```

## Benefits

- **Type Safety**: Avoid null pointer exceptions with Option types
- **Functional Programming**: Chain operations with map, filter, and match
- **Explicit Error Handling**: High-order function for handling checked exceptions
- **Better Code Organization**: Separate happy path from error handling
- **Self-Documenting Code**: Make potential failures visible in method signatures
