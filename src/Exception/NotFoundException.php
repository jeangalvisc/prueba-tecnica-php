<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * El recurso solicitado no existe (se traduce a HTTP 404).
 */
final class NotFoundException extends \RuntimeException
{
}
