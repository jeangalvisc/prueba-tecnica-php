<?php

declare(strict_types=1);

namespace Tests\Domain;

use App\Domain\EstadoReserva;
use PHPUnit\Framework\TestCase;

final class EstadoReservaTest extends TestCase
{
    public function testPendientePuedePasarAConfirmadaOCancelada(): void
    {
        $pendiente = EstadoReserva::Pendiente;

        $this->assertTrue($pendiente->puedeCambiarA(EstadoReserva::Confirmada));
        $this->assertTrue($pendiente->puedeCambiarA(EstadoReserva::Cancelada));
        $this->assertFalse($pendiente->puedeCambiarA(EstadoReserva::Pendiente));
    }

    public function testConfirmadaSoloPuedePasarACancelada(): void
    {
        $confirmada = EstadoReserva::Confirmada;

        $this->assertTrue($confirmada->puedeCambiarA(EstadoReserva::Cancelada));
        $this->assertFalse($confirmada->puedeCambiarA(EstadoReserva::Pendiente));
        $this->assertFalse($confirmada->puedeCambiarA(EstadoReserva::Confirmada));
    }

    public function testCanceladaEsEstadoFinal(): void
    {
        $cancelada = EstadoReserva::Cancelada;

        $this->assertSame([], $cancelada->transicionesPermitidas());
        $this->assertFalse($cancelada->puedeCambiarA(EstadoReserva::Pendiente));
        $this->assertFalse($cancelada->puedeCambiarA(EstadoReserva::Confirmada));
        $this->assertFalse($cancelada->puedeCambiarA(EstadoReserva::Cancelada));
    }
}
