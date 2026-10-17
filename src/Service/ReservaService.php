<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\EstadoReserva;
use App\Domain\FiltroReservas;
use App\Domain\NuevaReserva;
use App\Domain\Reserva;
use App\Domain\ReservaEvento;
use App\Domain\ResultadoPaginado;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Infrastructure\Clock;
use App\Repository\ReservaEventoRepository;
use App\Repository\ReservaRepository;
use App\Repository\TransactionManager;

/**
 * Casos de uso de las reservas (lógica de negocio).
 *
 * Orquesta dominio y persistencia: no sabe nada de HTTP ni de SQL.
 * Cada escritura y su evento de historial se guardan en la misma
 * transacción, así el historial nunca queda desincronizado.
 */
final class ReservaService
{
    public function __construct(
        private readonly ReservaRepository $reservas,
        private readonly ReservaEventoRepository $eventos,
        private readonly TransactionManager $transacciones,
        private readonly Clock $clock,
    ) {
    }

    public function listar(FiltroReservas $filtro): ResultadoPaginado
    {
        return $this->reservas->buscar($filtro);
    }

    /** @throws NotFoundException */
    public function obtener(int $id): Reserva
    {
        return $this->reservas->buscarPorId($id)
            ?? throw new NotFoundException(sprintf('No existe la reserva %d.', $id));
    }

    /**
     * @return list<ReservaEvento>
     *
     * @throws NotFoundException
     */
    public function historial(int $id): array
    {
        $this->obtener($id);

        return $this->eventos->listarPorReserva($id);
    }

    public function crear(NuevaReserva $datos): Reserva
    {
        $ahora = $this->clock->now();
        $reserva = Reserva::nueva($datos, $ahora);

        $id = $this->transacciones->transactional(function () use ($reserva, $ahora): int {
            $id = $this->reservas->insertar($reserva);
            $this->eventos->registrar(ReservaEvento::creacion($id, $ahora));

            return $id;
        });

        return $this->obtener($id);
    }

    /**
     * @throws NotFoundException  si la reserva no existe
     * @throws ConflictException  si la transición no está permitida o hubo un cambio concurrente
     */
    public function cambiarEstado(int $id, EstadoReserva $nuevoEstado): Reserva
    {
        $actual = $this->obtener($id);
        // El dominio decide si la transición es válida (lanza ConflictException si no).
        $actualizada = $actual->cambiarEstado($nuevoEstado);

        $this->transacciones->transactional(function () use ($id, $actual, $actualizada): void {
            $ok = $this->reservas->actualizarEstado($id, $actual->estado, $actualizada->estado);
            if (!$ok) {
                throw new ConflictException('La reserva ha sido modificada por otra operación. Recarga y vuelve a intentarlo.');
            }

            $this->eventos->registrar(ReservaEvento::cambioEstado(
                $id,
                $actual->estado,
                $actualizada->estado,
                $this->clock->now(),
            ));
        });

        return $actualizada;
    }
}
