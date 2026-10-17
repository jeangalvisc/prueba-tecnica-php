<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

/**
 * Entrada del historial de una reserva (timeline).
 */
final class ReservaEvento
{
    public const TIPO_CREADA = 'creada';

    public function __construct(
        public readonly ?int $id,
        public readonly int $reservaId,
        public readonly string $tipo,
        public readonly string $descripcion,
        public readonly DateTimeImmutable $fechaCreacion,
    ) {
    }

    public static function creacion(int $reservaId, DateTimeImmutable $fecha): self
    {
        return new self(
            null,
            $reservaId,
            self::TIPO_CREADA,
            sprintf('Reserva creada con estado %s.', EstadoReserva::Pendiente->value),
            $fecha,
        );
    }

    public static function cambioEstado(
        int $reservaId,
        EstadoReserva $anterior,
        EstadoReserva $nuevo,
        DateTimeImmutable $fecha,
    ): self {
        return new self(
            null,
            $reservaId,
            $nuevo->value,
            sprintf('Estado cambiado de %s a %s.', $anterior->value, $nuevo->value),
            $fecha,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'tipo'           => $this->tipo,
            'descripcion'    => $this->descripcion,
            'fecha_creacion' => $this->fechaCreacion->format(DATE_ATOM),
        ];
    }
}
