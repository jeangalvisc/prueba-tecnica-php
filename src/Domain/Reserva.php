<?php

declare(strict_types=1);

namespace App\Domain;

use App\Exception\ConflictException;
use DateTimeImmutable;

/**
 * Entidad Reserva. Inmutable: los cambios de estado devuelven una nueva
 * instancia, y solo se permiten las transiciones definidas en EstadoReserva.
 */
final class Reserva
{
    /**
     * @param string $importe decimal con 2 cifras ("645.00"); se evita float para dinero
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $nombreHuesped,
        public readonly string $emailHuesped,
        public readonly string $nombreAlojamiento,
        public readonly DateTimeImmutable $fechaEntrada,
        public readonly DateTimeImmutable $fechaSalida,
        public readonly EstadoReserva $estado,
        public readonly string $importe,
        public readonly ?string $notas,
        public readonly DateTimeImmutable $fechaCreacion,
    ) {
    }

    public static function nueva(NuevaReserva $datos, DateTimeImmutable $ahora): self
    {
        return new self(
            id: null,
            nombreHuesped: $datos->nombreHuesped,
            emailHuesped: $datos->emailHuesped,
            nombreAlojamiento: $datos->nombreAlojamiento,
            fechaEntrada: $datos->fechaEntrada,
            fechaSalida: $datos->fechaSalida,
            estado: EstadoReserva::Pendiente,
            importe: $datos->importe,
            notas: $datos->notas,
            fechaCreacion: $ahora,
        );
    }

    /**
     * @throws ConflictException si la transición no está permitida
     */
    public function cambiarEstado(EstadoReserva $nuevo): self
    {
        if ($nuevo === $this->estado) {
            throw new ConflictException(sprintf('La reserva ya está %s.', $nuevo->value));
        }

        if (!$this->estado->puedeCambiarA($nuevo)) {
            throw new ConflictException(sprintf(
                'No se puede cambiar una reserva %s a %s.',
                $this->estado->value,
                $nuevo->value,
            ));
        }

        return new self(
            $this->id,
            $this->nombreHuesped,
            $this->emailHuesped,
            $this->nombreAlojamiento,
            $this->fechaEntrada,
            $this->fechaSalida,
            $nuevo,
            $this->importe,
            $this->notas,
            $this->fechaCreacion,
        );
    }

    public function noches(): int
    {
        return (int) $this->fechaEntrada->diff($this->fechaSalida)->days;
    }

    public function puedeConfirmarse(): bool
    {
        return $this->estado->puedeCambiarA(EstadoReserva::Confirmada);
    }

    public function puedeCancelarse(): bool
    {
        return $this->estado->puedeCambiarA(EstadoReserva::Cancelada);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre_huesped' => $this->nombreHuesped,
            'email_huesped' => $this->emailHuesped,
            'nombre_alojamiento' => $this->nombreAlojamiento,
            'fecha_entrada' => $this->fechaEntrada->format('Y-m-d'),
            'fecha_salida' => $this->fechaSalida->format('Y-m-d'),
            'noches' => $this->noches(),
            'estado' => $this->estado->value,
            'importe' => $this->importe,
            'notas' => $this->notas,
            'fecha_creacion' => $this->fechaCreacion->format(DATE_ATOM),
        ];
    }
}
