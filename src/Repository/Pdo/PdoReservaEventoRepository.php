<?php

declare(strict_types=1);

namespace App\Repository\Pdo;

use App\Domain\ReservaEvento;
use App\Repository\ReservaEventoRepository;
use DateTimeImmutable;
use PDO;

final class PdoReservaEventoRepository implements ReservaEventoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(ReservaEvento $evento): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reserva_evento (reserva_id, tipo, descripcion, fecha_creacion)
             VALUES (:reserva_id, :tipo, :descripcion, :fecha_creacion)',
        );

        $stmt->execute([
            'reserva_id'     => $evento->reservaId,
            'tipo'           => $evento->tipo,
            'descripcion'    => $evento->descripcion,
            'fecha_creacion' => $evento->fechaCreacion->format('Y-m-d H:i:s'),
        ]);
    }

    public function listarPorReserva(int $reservaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, reserva_id, tipo, descripcion, fecha_creacion
             FROM reserva_evento
             WHERE reserva_id = :reserva_id
             ORDER BY fecha_creacion ASC, id ASC',
        );
        $stmt->bindValue('reserva_id', $reservaId, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            static fn (array $row): ReservaEvento => new ReservaEvento(
                (int) $row['id'],
                (int) $row['reserva_id'],
                (string) $row['tipo'],
                (string) $row['descripcion'],
                new DateTimeImmutable((string) $row['fecha_creacion']),
            ),
            $stmt->fetchAll(),
        );
    }
}
