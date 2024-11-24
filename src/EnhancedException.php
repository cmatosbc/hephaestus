<?php

namespace Hephaestus;

/**
 * EnhancedException provides enhanced exception handling capabilities with state tracking
 * and exception history management.
 */
class EnhancedException extends \Exception
{
    /**
     * @var array<string, mixed> Stores the state snapshots at different points
     */
    private array $stateHistory = [];

    /**
     * @var \Throwable[] Stores the chain of exceptions that occurred
     */
    private array $exceptionHistory = [];

    /**
     * Creates a new EnhancedException instance
     *
     * @param string $message The exception message
     * @param int $code The exception code (optional)
     * @param \Throwable|null $previous The previous throwable (optional)
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        if ($previous) {
            $this->addToHistory($previous);
        }
    }

    /**
     * Saves a state snapshot with an optional label
     *
     * @param mixed $state The state to save
     * @param string $context Optional label for the state
     * @return self
     */
    public function saveState(mixed $state, string $context = 'default'): self
    {
        $this->stateHistory[$context] = [
            'state' => $state,
            'timestamp' => microtime(true),
        ];
        return $this;
    }

    /**
     * Retrieves a specific state by its label
     *
     * @param string $context The label of the state to retrieve
     * @return mixed|null The state if found, null otherwise
     */
    public function getState(string $context = 'default'): mixed
    {
        return $this->stateHistory[$context]['state'] ?? null;
    }

    /**
     * Gets all saved states
     *
     * @return array<string, array{timestamp: float, state: mixed}>
     */
    public function getAllStates(): array
    {
        return $this->stateHistory;
    }

    /**
     * Adds an exception to the history
     *
     * @param \Throwable $exception
     * @return self
     */
    public function addToHistory(\Throwable $exception): self
    {
        $this->exceptionHistory[] = $exception;
        return $this;
    }

    /**
     * Gets the complete exception history
     *
     * @return \Throwable[]
     */
    public function getExceptionHistory(): array
    {
        return $this->exceptionHistory;
    }

    /**
     * Gets the most recent exception from history
     *
     * @return \Throwable|null
     */
    public function getLastException(): ?\Throwable
    {
        if (empty($this->exceptionHistory)) {
            return null;
        }
        return end($this->exceptionHistory);
    }

    /**
     * Checks if a specific exception type exists in the history
     *
     * @param string $exceptionClass
     * @return bool
     */
    public function hasExceptionOfType(string $exceptionClass): bool
    {
        foreach ($this->exceptionHistory as $exception) {
            if ($exception instanceof $exceptionClass) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets all exceptions of a specific type from the history
     *
     * @param string $exceptionClass
     * @return \Exception[]
     */
    public function getExceptionsOfType(string $exceptionClass): array
    {
        return array_filter(
            $this->exceptionHistory,
            fn($exception) => $exception instanceof $exceptionClass
        );
    }

    /**
     * Clears all saved states and exception history
     *
     * @return self
     */
    public function clearHistory(): self
    {
        $this->stateHistory = [];
        $this->exceptionHistory = [];
        return $this;
    }

    public function withExceptionHistory(array $exceptions): self
    {
        foreach ($exceptions as $exception) {
            $this->addToHistory($exception);
        }
        return $this;
    }
}
