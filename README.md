# Hephaestus
God-like error handling library for modern PHP programmers.

## Features

- **Option Type (Some/None)**: Rust-inspired Option type for handling nullable values elegantly
- **CheckedException**: Java-inspired checked exceptions for explicit error handling
- **Retry Logic**: Elegant retry mechanism for operations that might fail temporarily
- **EnhancedException**: Advanced error handling with state tracking and exception history management

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

### Retry Logic

The library provides a retry mechanism for operations that might fail temporarily:

```php
use function Hephaestus\withRetryBeforeFailing;

// Create an HTTP client that retries 3 times
$retrier = withRetryBeforeFailing(3);

try {
    $result = $retrier(function() {
        $response = file_get_contents('https://api.example.com/data');
        if ($response === false) {
            throw new \Exception("Failed to fetch data");
        }
        return json_decode($response, true);
    });
    echo "Data fetched successfully!";
} catch (\Exception $e) {
    echo "All retry attempts failed: " . $e->getMessage();
}

// Combining with Option type
function fetchDataSafely($url): Option {
    $retrier = withRetryBeforeFailing(3);
    try {
        $data = $retrier(function() use ($url) {
            $response = file_get_contents($url);
            if ($response === false) {
                throw new \Exception("Failed to fetch data");
            }
            return json_decode($response, true);
        });
        return Some($data);
    } catch (\Exception $e) {
        error_log("Failed to fetch data after retries: " . $e->getMessage());
        return None();
    }
}

// Usage
$data = fetchDataSafely('https://api.example.com/data')
    ->map(fn($d) => $d['value'])
    ->getOrElse('default value');
```

The retry mechanism is useful for:
- Network operations that might fail temporarily
- Database operations with transient failures
- Any operation that might succeed on a subsequent attempt

The retrier will:
1. Attempt the operation
2. On failure, wait 1 second
3. Retry up to the specified number of times
4. If all attempts fail, throw an exception with the history of failures

### EnhancedException

The EnhancedException class provides advanced error handling capabilities with state tracking and exception history management:

```php
use Hephaestus\EnhancedException;

class DatabaseException extends EnhancedException {}

function performDatabaseOperation() {
    try {
        $dbState = ['connection' => 'active', 'query' => 'SELECT * FROM users'];
        
        // Simulate database error
        throw new DatabaseException(
            "Failed to execute query",
            0,
            new \PDOException("Connection lost")
        );
    } catch (DatabaseException $e) {
        // Save the database state at the time of failure
        $e->saveState($dbState, 'database_state');
        
        // Add additional context
        $e->addToExceptionHistory(new \RuntimeException("Retry attempt failed"));
        
        // Get all saved states
        $states = $e->getAllStates();
        error_log("Database state at failure: " . json_encode($states['database_state']));
        
        // Check for specific types of errors
        if ($e->hasExceptionOfType(\PDOException::class)) {
            $pdoErrors = $e->getExceptionsOfType(\PDOException::class);
            // Handle PDO-specific errors
        }
        
        // Get the most recent error
        $lastError = $e->getLastException();
        error_log("Last error: " . $lastError->getMessage());
        
        throw $e; // Re-throw with all the collected context
    }
}

// Using with Option type for safer error handling
function fetchUserSafely(int $userId): Option {
    try {
        $result = performDatabaseOperation();
        return Some($result);
    } catch (DatabaseException $e) {
        // We can examine the complete error history
        foreach ($e->getExceptionHistory() as $error) {
            error_log("Error in chain: " . $error->getMessage());
        }
        return None();
    }
}

// Usage
$userData = fetchUserSafely(42)
    ->map(fn($user) => $user['name'])
    ->getOrElse('Unknown User');
```

Key features of EnhancedException:

1. **State Tracking**
   - Save snapshots of application state at different points
   - Label states for easy retrieval
   - Track the progression of state changes

2. **Exception History**
   - Maintain a chain of related exceptions
   - Filter exceptions by type
   - Access the most recent exception
   - Track the complete error context

3. **Integration with Option**
   - Combine with Option type for functional error handling
   - Chain operations safely after error recovery
   - Provide default values when errors occur

The EnhancedException is particularly useful for:
- Complex operations with multiple potential failure points
- Debugging distributed systems
- Tracking state changes during failures
- Building comprehensive error reports

## Benefits

- **Type Safety**: Avoid null pointer exceptions with Option types
- **Functional Programming**: Chain operations with map, filter, and match
- **Explicit Error Handling**: High-order function for handling checked exceptions
- **Better Code Organization**: Separate happy path from error handling
- **Self-Documenting Code**: Make potential failures visible in method signatures
- **Resilient Operations**: Built-in retry mechanism for transient failures
