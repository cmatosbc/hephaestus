<?php

namespace Hephaestus\Bundle\Service;

use Hephaestus\Bundle\Exception\SymfonyEnhancedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;

class ExceptionHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private bool $debug = false
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        if ($exception instanceof SymfonyEnhancedException) {
            $this->handleEnhancedException($event, $exception);
            return;
        }

        // Convert other exceptions to SymfonyEnhancedException
        if ($this->shouldConvertException($exception)) {
            $enhancedException = new SymfonyEnhancedException(
                $exception->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                [],
                $exception->getCode(),
                $exception
            );
            $this->handleEnhancedException($event, $enhancedException);
        }
    }

    private function handleEnhancedException(ExceptionEvent $event, SymfonyEnhancedException $exception): void
    {
        // Log the exception with its full history
        $this->logException($exception);

        // Set the response
        $event->setResponse($exception->toResponse());
    }

    private function shouldConvertException(\Throwable $exception): bool
    {
        // Add logic here to determine which exceptions should be converted
        // For example, you might want to exclude some built-in Symfony exceptions
        return true;
    }

    private function logException(SymfonyEnhancedException $exception): void
    {
        $context = [
            'exception_class' => get_class($exception),
            'status_code' => $exception->getStatusCode(),
            'history' => array_map(
                fn(\Throwable $e) => [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ],
                $exception->getExceptionHistory()
            )
        ];

        $this->logger->error($exception->getMessage(), $context);
    }
}
