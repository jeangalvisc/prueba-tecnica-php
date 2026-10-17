<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\EstadoReserva;
use App\Domain\FiltroReservas;
use App\Domain\Reserva;
use App\Domain\ResultadoPaginado;

/**
 * Acceso a datos de reservas. La lógica de negocio no sabe nada de SQL:
 * depende de esta interfaz, lo que permite testearla con un repositorio en memoria.
 */
interface ReservaRepository
{
    public function buscar(FiltroReservas $filtro): ResultadoPaginado;

    public function buscarPorId(int $id): ?Reserva;

    /** @return int id generado */
    public function insertar(Reserva $reserva): int;

    /**
     * Cambia el estado solo si la reserva sigue en $actual (control de
     * concurrencia optimista). Devuelve false si otro proceso se adelantó.
     */
    public function actualizarEstado(int $id, EstadoReserva $actual, EstadoReserva $nuevo): bool;
}
