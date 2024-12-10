<?php

namespace Hephaestus;

/**
 * Executes a callable with enhanced exception handling.
 * Handles CheckedException specifically, providing detailed error messages.
 *
 * @param \Closure|array|string $func The function or callable to be executed.
 * @param mixed ...$args Arguments to be passed to the callable.
 * @return mixed The result of the callable execution, or null if a CheckedException was handled.
 * @throws CheckedException if a CheckedException is thrown during the callable execution.
 */
function withCheckedExceptionHandling(\Closure|array|string $func, ...$args)
{
    try {
        if ($func instanceof \Closure) {
            return $func(...$args);
        }

        if (is_array($func) || is_string($func)) {
            return call_user_func($func, ...$args);
        }
    } catch (CheckedException $e) {
        echo "Handled CheckedException: " . $e->getMessage() . PHP_EOL;
        return null;
    }
}

/**
 * Creates a retry wrapper that will attempt an operation multiple times before failing.
 * The operation will be retried with a 1-second delay between attempts.
 *
 * @param int $retries The maximum number of retry attempts (default: 3)
 * @return \Closure A function that takes an operation closure and executes it with retry logic
 * @throws \Exception When all retry attempts fail, wrapping the last caught exception
 *
 * Example:
 *   $retrier = withRetryBeforeFailing(3);
 *   $result = $retrier(function() {
 *       // potentially failing operation
 *   });
 */
function withRetryBeforeFailing(int $retries = 3)
{
    return function (\Closure $operation) use ($retries) {
        $attempt = 0;
        while ($attempt < $retries) {
            try {
                return $operation();
            } catch (\Exception $e) {
                $attempt++;
                error_log("Operation attempt $attempt failed: " . $e->getMessage());
                if ($attempt >= $retries) {
                    throw new \Exception("All $retries attempts failed.", 0, $e);
                }
                sleep(1);
            }
        }
    };
}

/**
 * Creates an instance of Some, representing a present value.
 *
 * @param mixed $value The value to be wrapped in a Some instance.
 * @return Some The Some instance wrapping the provided value.
 */
function Some($value): Some
{
    return Option::some($value);
}

/**
 * Creates a None instance.
 *
 * @return None
 */
function None(): None
{
    return Option::none();
}
