<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Los datos de entrada no son válidos (se traduce a HTTP 400).
 * Lleva un mensaje por campo para que el cliente pueda mostrarlos.
 */
final class ValidationException extends \RuntimeException
{
    /** @param array<string, string> $errors campo => mensaje */
    public function __construct(
        private readonly array $errors,
        string $message = 'Los datos enviados no son válidos.',
    ) {
        parent::__construct($message);
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
