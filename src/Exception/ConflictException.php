<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * La operación choca con el estado actual del recurso, p. ej. intentar
 * confirmar una reserva cancelada (se traduce a HTTP 409).
 */
final class ConflictException extends \RuntimeException
{
}
