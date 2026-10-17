<?php

declare(strict_types=1);

namespace App\Repository\Pdo;

use App\Domain\EstadoReserva;
use App\Domain\FiltroReservas;
use App\Domain\Reserva;
use App\Domain\ResultadoPaginado;
use App\Repository\ReservaRepository;
use DateTimeImmutable;
use PDO;

/**
 * Implementación MySQL/MariaDB del repositorio de reservas.
 *
 * Todas las consultas son preparadas. El SQL dinámico de los filtros se
 * construye solo con fragmentos fijos escritos aquí; los valores del usuario
 * viajan siempre como parámetros.
 */
final class PdoReservaRepository implements ReservaRepository
{
    private const COLUMNAS = 'id, nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada,
        fecha_salida, estado, importe, notas, fecha_creacion';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscar(FiltroReservas $filtro): ResultadoPaginado
    {
        [$where, $params] = $this->construirWhere($filtro);

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM reserva {$where}");
        $this->bindAll($count, $params);
        $count->execute();
        $total = (int) $count->fetchColumn();

        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNAS . " FROM reserva {$where}
             ORDER BY fecha_entrada DESC, id DESC
             LIMIT :limite OFFSET :desplazamiento",
        );
        $this->bindAll($stmt, $params);
        $stmt->bindValue('limite', $filtro->porPagina, PDO::PARAM_INT);
        $stmt->bindValue('desplazamiento', $filtro->offset(), PDO::PARAM_INT);
        $stmt->execute();

        $items = array_map(fn (array $row): Reserva => $this->hidratar($row), $stmt->fetchAll());

        return new ResultadoPaginado($items, $total, $filtro->pagina, $filtro->porPagina);
    }

    public function buscarPorId(int $id): ?Reserva
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNAS . ' FROM reserva WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row === false ? null : $this->hidratar($row);
    }

    public function insertar(Reserva $reserva): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reserva
                (nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida,
                 estado, importe, notas, fecha_creacion)
             VALUES
                (:nombre_huesped, :email_huesped, :nombre_alojamiento, :fecha_entrada, :fecha_salida,
                 :estado, :importe, :notas, :fecha_creacion)',
        );

        $stmt->execute([
            'nombre_huesped'     => $reserva->nombreHuesped,
            'email_huesped'      => $reserva->emailHuesped,
            'nombre_alojamiento' => $reserva->nombreAlojamiento,
            'fecha_entrada'      => $reserva->fechaEntrada->format('Y-m-d'),
            'fecha_salida'       => $reserva->fechaSalida->format('Y-m-d'),
            'estado'             => $reserva->estado->value,
            'importe'            => $reserva->importe,
            'notas'              => $reserva->notas,
            'fecha_creacion'     => $reserva->fechaCreacion->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function actualizarEstado(int $id, EstadoReserva $actual, EstadoReserva $nuevo): bool
    {
        $stmt = $this->pdo->prepare('UPDATE reserva SET estado = :nuevo WHERE id = :id AND estado = :actual');
        $stmt->bindValue('nuevo', $nuevo->value);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->bindValue('actual', $actual->value);
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }

    /** @return array{string, array<string, string>} */
    private function construirWhere(FiltroReservas $filtro): array
    {
        $condiciones = [];
        $params = [];

        if ($filtro->estado !== null) {
            $condiciones[] = 'estado = :estado';
            $params['estado'] = $filtro->estado->value;
        }

        // Solapamiento de la estancia [entrada, salida) con el rango [desde, hasta].
        if ($filtro->desde !== null) {
            $condiciones[] = 'fecha_salida > :desde';
            $params['desde'] = $filtro->desde->format('Y-m-d');
        }

        if ($filtro->hasta !== null) {
            $condiciones[] = 'fecha_entrada <= :hasta';
            $params['hasta'] = $filtro->hasta->format('Y-m-d');
        }

        if ($filtro->busqueda !== null) {
            // Se escapan los comodines de LIKE para que "%" o "_" se busquen literalmente.
            $condiciones[] = 'nombre_huesped LIKE :busqueda';
            $params['busqueda'] = '%' . addcslashes($filtro->busqueda, '\\%_') . '%';
        }

        $where = $condiciones === [] ? '' : 'WHERE ' . implode(' AND ', $condiciones);

        return [$where, $params];
    }

    /** @param array<string, string> $params */
    private function bindAll(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
    }

    /** @param array<string, mixed> $row */
    private function hidratar(array $row): Reserva
    {
        return new Reserva(
            id: (int) $row['id'],
            nombreHuesped: (string) $row['nombre_huesped'],
            emailHuesped: (string) $row['email_huesped'],
            nombreAlojamiento: (string) $row['nombre_alojamiento'],
            fechaEntrada: new DateTimeImmutable((string) $row['fecha_entrada']),
            fechaSalida: new DateTimeImmutable((string) $row['fecha_salida']),
            estado: EstadoReserva::from((string) $row['estado']),
            importe: (string) $row['importe'],
            notas: $row['notas'] !== null ? (string) $row['notas'] : null,
            fechaCreacion: new DateTimeImmutable((string) $row['fecha_creacion']),
        );
    }
}
