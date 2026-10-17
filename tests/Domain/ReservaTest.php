<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\EstadoReserva;
use App\Domain\NuevaReserva;
use App\Domain\Reserva;
use App\Exception\ConflictException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ReservaTest extends TestCase
{
    public function testCrearReservaNuevaNaceEnEstadoPendiente(): void
    {
        $dto = new NuevaReserva(
            nombreHuesped: 'Ana García',
            emailHuesped: 'ana@example.com',
            nombreAlojamiento: 'Hotel Sol',
            fechaEntrada: new DateTimeImmutable('2026-11-01'),
            fechaSalida: new DateTimeImmutable('2026-11-05'),
            importe: '400.00',
            notas: 'Piso alto',
        );

        $ahora = new DateTimeImmutable('2026-10-17 12:00:00');
        $reserva = Reserva::nueva($dto, $ahora);

        $this->assertNull($reserva->id);
        $this->assertSame(EstadoReserva::Pendiente, $reserva->estado);
        $this->assertSame('Ana García', $reserva->nombreHuesped);
        $this->assertSame(4, $reserva->noches());
        $this->assertTrue($reserva->puedeConfirmarse());
        $this->assertTrue($reserva->puedeCancelarse());
    }

    public function testCambioEstadoValidoDevuelveNuevaInstanciaInmutable(): void
    {
        $dto = new NuevaReserva(
            nombreHuesped: 'Ana García',
            emailHuesped: 'ana@example.com',
            nombreAlojamiento: 'Hotel Sol',
            fechaEntrada: new DateTimeImmutable('2026-11-01'),
            fechaSalida: new DateTimeImmutable('2026-11-03'),
            importe: '200.00',
            notas: null,
        );

        $original = Reserva::nueva($dto, new DateTimeImmutable('2026-10-17 12:00:00'));
        $confirmada = $original->cambiarEstado(EstadoReserva::Confirmada);

        $this->assertSame(EstadoReserva::Pendiente, $original->estado);
        $this->assertSame(EstadoReserva::Confirmada, $confirmada->estado);
        $this->assertFalse($confirmada->puedeConfirmarse());
        $this->assertTrue($confirmada->puedeCancelarse());
    }

    public function testCambioEstadoInvalidoLanzaConflictException(): void
    {
        $dto = new NuevaReserva(
            nombreHuesped: 'Ana García',
            emailHuesped: 'ana@example.com',
            nombreAlojamiento: 'Hotel Sol',
            fechaEntrada: new DateTimeImmutable('2026-11-01'),
            fechaSalida: new DateTimeImmutable('2026-11-03'),
            importe: '200.00',
            notas: null,
        );

        $reserva = Reserva::nueva($dto, new DateTimeImmutable('2026-10-17 12:00:00'));
        $cancelada = $reserva->cambiarEstado(EstadoReserva::Cancelada);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('No se puede cambiar una reserva cancelada a confirmada');
        $cancelada->cambiarEstado(EstadoReserva::Confirmada);
    }

    public function testCambiarAlMismoEstadoLanzaConflictException(): void
    {
        $dto = new NuevaReserva(
            nombreHuesped: 'Ana García',
            emailHuesped: 'ana@example.com',
            nombreAlojamiento: 'Hotel Sol',
            fechaEntrada: new DateTimeImmutable('2026-11-01'),
            fechaSalida: new DateTimeImmutable('2026-11-03'),
            importe: '200.00',
            notas: null,
        );

        $reserva = Reserva::nueva($dto, new DateTimeImmutable('2026-10-17 12:00:00'));

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('La reserva ya está pendiente');
        $reserva->cambiarEstado(EstadoReserva::Pendiente);
    }
}
