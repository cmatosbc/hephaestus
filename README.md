# Hephaestus
God-like error handling library for modern PHP programmers.

## Features

- **Option Type (Some/None)**: Rust-inspired Option type for handling nullable values elegantly
- **CheckedException**: Java-inspired checked exceptions for explicit error handling
- **Retry Logic**: Elegant retry mechanism for operations that might fail temporarily
- **EnhancedException**: Advanced error handling with state tracking and exception history management
- **Symfony Bundle**: Seamless integration with Symfony framework for enhanced error handling

## Requirements

- PHP 8.1 or higher
- Symfony 7.1 or higher (for bundle integration)

## Installation

```bash
composer require cmatosbc/hephaestus
```

For Symfony integration, register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    Hephaestus\Bundle\HephaestusBundle::class => ['all' => true],
];
```

## Configuration

When using the Symfony bundle, you can configure it in your `config/packages/hephaestus.yaml`:

```yaml
hephaestus:
    max_retries: 3
    retry_delay: 1
    logging:
        enabled: true
        channel: 'hephaestus'
```

## Usage

### Option Factory (Symfony Bundle)

The bundle provides an `OptionFactory` service for creating Option types with retry capabilities:

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

### Enhanced Exception Handling (Symfony Bundle)

The bundle provides `SymfonyEnhancedException` for HTTP-aware error handling:

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
        return $this->optionFactory->fromCallable(
            fn() => $this->userRepository->find($id)
        )
        ->getOrThrow(fn() => new UserNotFoundException($id))
        ->toResponse();
    }
}
```

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

## Symfony Bundle

The Hephaestus Bundle provides seamless integration with Symfony applications, offering enhanced error handling, Option type integration with forms, and configurable logging.

### Installation

1. Install via Composer:
```bash
composer require cmatosbc/hephaestus
```

2. Register the bundle in `config/bundles.php`:
```php
return [
    // ...
    Hephaestus\Bundle\HephaestusBundle::class => ['all' => true],
];
```

3. Create configuration file `config/packages/hephaestus.yaml`:
```yaml
hephaestus:
    exception_handling:
        max_retries: 3    # Maximum retry attempts for transient failures
        retry_delay: 1    # Delay in seconds between retries
    logging:
        enabled: true     # Enable enhanced exception logging
        channel: hephaestus  # Custom logging channel
```

### Features

#### 1. Enhanced Exception Handling

The bundle provides a Symfony-aware exception handler that automatically converts exceptions to HTTP responses and includes detailed error context:

```php
use Hephaestus\Bundle\Exception\SymfonyEnhancedException;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    public function create(Request $request): Response
    {
        try {
            $result = $this->userService->createUser($request->request->all());
            return $this->json($result);
        } catch (ValidationException $e) {
            throw new SymfonyEnhancedException(
                'Invalid user data',
                Response::HTTP_BAD_REQUEST,
                [],
                0,
                $e
            );
        } catch (DatabaseException $e) {
            // The exception will be automatically logged with full context
            throw new SymfonyEnhancedException(
                'Failed to create user',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                [],
                0,
                $e
            );
        }
    }
}
```

The exception handler will automatically:
- Convert exceptions to appropriate HTTP responses
- Log detailed error context including exception history
- Maintain the full exception chain for debugging
- Format error responses as JSON with proper status codes

#### 2. Option Type Integration with Forms

The bundle provides an `OptionFactory` service that integrates the Option type with Symfony forms:

```php
use Hephaestus\Bundle\Service\OptionFactory;

class RegistrationController extends AbstractController
{
    public function __construct(
        private OptionFactory $optionFactory
    ) {}

    public function register(Request $request): Response
    {
        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);

        return $this->optionFactory->fromForm($form)
            ->map(fn($data) => $this->userService->register($data))
            ->match(
                some: fn($user) => $this->redirectToRoute('app_login'),
                none: fn() => $this->render('registration/register.html.twig', [
                    'form' => $form->createView(),
                ])
            );
    }
}
```

The `OptionFactory` provides several utility methods:
```php
// Create Option from nullable value
$userOption = $optionFactory->fromNullable($user);

// Create Option from array key
$nameOption = $optionFactory->fromArrayKey($data, 'name');

// Create Option from callable that might fail
$resultOption = $optionFactory->fromCallable([$service, 'riskyOperation']);
```

#### 3. Exception State Tracking

The `SymfonyEnhancedException` maintains state information and exception history:

```php
use Hephaestus\Bundle\Exception\SymfonyEnhancedException;

class PaymentService
{
    public function processPayment(array $paymentData): void
    {
        try {
            $this->validatePayment($paymentData);
            $this->performPayment($paymentData);
        } catch (PaymentValidationException $e) {
            $exception = new SymfonyEnhancedException(
                'Payment validation failed',
                Response::HTTP_BAD_REQUEST,
                [],
                0,
                $e
            );
            
            // Add context about the failed validation
            $exception->saveState([
                'payment_data' => $paymentData,
                'validation_errors' => $e->getErrors(),
            ], 'validation_context');
            
            throw $exception;
        } catch (PaymentProcessingException $e) {
            $exception = new SymfonyEnhancedException(
                'Payment processing failed',
                Response::HTTP_SERVICE_UNAVAILABLE,
                [],
                0,
                $e
            );
            
            // Add payment processing state
            $exception->saveState([
                'payment_id' => $paymentData['id'],
                'processor_response' => $e->getResponse(),
            ], 'payment_context');
            
            throw $exception;
        }
    }
}
```

The exception state and history will be automatically:
- Logged with full context
- Included in debug responses (in dev environment)
- Available for custom error handling

#### 4. Retry Logic Integration

The bundle integrates the retry mechanism with Symfony's service container:

```php
use function Hephaestus\withRetryBeforeFailing;

class ExternalServiceClient
{
    public function __construct(
        private HttpClientInterface $client,
        private LoggerInterface $logger
    ) {}

    public function fetchData(): array
    {
        $retrier = withRetryBeforeFailing(3); // Use configured retry count
        
        return $retrier(function() {
            $response = $this->client->request('GET', 'https://api.example.com/data');
            
            if ($response->getStatusCode() !== 200) {
                throw new ServiceException('Failed to fetch data');
            }
            
            return $response->toArray();
        });
    }
}
```

### Best Practices

1. **Exception Handling**
   - Use `SymfonyEnhancedException` for HTTP-aware error handling
   - Add relevant state information using `saveState()`
   - Let the bundle handle logging and response formatting

2. **Form Handling**
   - Use `OptionFactory->fromForm()` for clean form processing
   - Chain operations using `map()` and `filter()`
   - Use `match()` for explicit success/failure handling

3. **Service Integration**
   - Inject `OptionFactory` when working with forms or nullable values
   - Configure retry attempts based on operation type
   - Use the logging channel for filtered log access

### Testing

The bundle provides test utilities and assertions:

```php
use Hephaestus\Bundle\Test\OptionAssertions;

class UserServiceTest extends TestCase
{
    use OptionAssertions;

    public function testUserCreation(): void
    {
        $result = $this->userService->createUser($userData);
        
        $this->assertOptionIsSome($result);
        $this->assertOptionContains($result, fn($user) => 
            $user->getEmail() === $userData['email']
        );
    }
}
```

For more examples and detailed documentation, visit the [Symfony Bundle Documentation](https://github.com/cmatosbc/hephaestus/wiki/Symfony-Bundle).

## Benefits

- **Type Safety**: Avoid null pointer exceptions with Option types
- **Functional Programming**: Chain operations with map, filter, and match
- **Explicit Error Handling**: High-order function for handling checked exceptions
- **Better Code Organization**: Separate happy path from error handling
- **Self-Documenting Code**: Make potential failures visible in method signatures
- **Resilient Operations**: Built-in retry mechanism for transient failures

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
