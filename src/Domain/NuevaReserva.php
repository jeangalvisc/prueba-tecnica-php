<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

/**
 * Datos ya validados para crear una reserva (DTO de entrada).
 * Si existe un objeto de este tipo, sus datos son correctos.
 */
final class NuevaReserva
{
    public function __construct(
        public readonly string $nombreHuesped,
        public readonly string $emailHuesped,
        public readonly string $nombreAlojamiento,
        public readonly DateTimeImmutable $fechaEntrada,
        public readonly DateTimeImmutable $fechaSalida,
        public readonly string $importe,
        public readonly ?string $notas,
    ) {
    }
}
