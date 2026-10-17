<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Error propio de la capa HTTP (método no permitido, JSON mal formado,
 * Content-Type incorrecto...). El dominio no la conoce.
 */
final class HttpException extends \RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly int $status,
        string $message,
        private readonly array $headers = [],
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }
}
