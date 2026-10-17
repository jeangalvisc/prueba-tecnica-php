<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\ReservaEvento;

interface ReservaEventoRepository
{
    public function registrar(ReservaEvento $evento): void;

    /** @return list<ReservaEvento> en orden cronológico */
    public function listarPorReserva(int $reservaId): array;
}
