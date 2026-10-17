<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Estados posibles de una reserva y sus transiciones válidas.
 *
 *   pendiente ──► confirmada ──► cancelada
 *       └────────────────────────►┘
 *
 * "cancelada" es un estado final.
 */
enum EstadoReserva: string
{
    case Pendiente = 'pendiente';
    case Confirmada = 'confirmada';
    case Cancelada = 'cancelada';

    /** @return list<self> */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::Pendiente  => [self::Confirmada, self::Cancelada],
            self::Confirmada => [self::Cancelada],
            self::Cancelada  => [],
        };
    }

    public function puedeCambiarA(self $nuevo): bool
    {
        return in_array($nuevo, $this->transicionesPermitidas(), true);
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente  => 'Pendiente',
            self::Confirmada => 'Confirmada',
            self::Cancelada  => 'Cancelada',
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_map(static fn (self $e): string => $e->value, self::cases());
    }
}
