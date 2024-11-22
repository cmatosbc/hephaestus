<?php

namespace Hephaestus\Bundle\Exception;

use Hephaestus\EnhancedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SymfonyEnhancedException extends EnhancedException implements HttpExceptionInterface
{
    private ?Response $response = null;
    private int $statusCode;
    private array $headers;

    public function __construct(
        string $message = "",
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        array $headers = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setResponse(Response $response): self
    {
        $this->response = $response;
        return $this;
    }

    public function toResponse(): Response
    {
        if ($this->response) {
            return $this->response;
        }

        $data = [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'status' => $this->getStatusCode(),
            'exception_history' => array_map(
                fn(\Throwable $e) => [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ],
                $this->getExceptionHistory()
            )
        ];

        return new Response(
            json_encode($data, JSON_PRETTY_PRINT),
            $this->getStatusCode(),
            array_merge(
                ['Content-Type' => 'application/json'],
                $this->getHeaders()
            )
        );
    }
}
