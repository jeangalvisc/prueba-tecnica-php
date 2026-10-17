<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Domain\EstadoReserva;
use App\Domain\FiltroReservas;
use App\Domain\NuevaReserva;
use App\Domain\Reserva;
use App\Domain\ReservaEvento;
use App\Domain\ResultadoPaginado;
use App\Exception\NotFoundException;
use App\Infrastructure\Clock;
use App\Repository\ReservaEventoRepository;
use App\Repository\ReservaRepository;
use App\Repository\TransactionManager;
use App\Service\ReservaService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ReservaServiceTest extends TestCase
{
    private InMemoryReservaRepository $reservaRepo;
    private InMemoryEventoRepository $eventoRepo;
    private TransactionManager $transacciones;
    private Clock $clock;
    private ReservaService $service;

    protected function setUp(): void
    {
        $this->reservaRepo = new InMemoryReservaRepository();
        $this->eventoRepo = new InMemoryEventoRepository();
        $this->transacciones = new class implements TransactionManager {
            public function transactional(callable $operacion): mixed
            {
                return $operacion();
            }
        };
        $this->clock = new class implements Clock {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-10-17 10:00:00');
            }
        };

        $this->service = new ReservaService(
            $this->reservaRepo,
            $this->eventoRepo,
            $this->transacciones,
            $this->clock,
        );
    }

    public function testCrearReservaRegistraReservaYEventoCreacion(): void
    {
        $dto = new NuevaReserva(
            nombreHuesped: 'David Bisbal',
            emailHuesped: 'david@example.com',
            nombreAlojamiento: 'Hotel Almería',
            fechaEntrada: new DateTimeImmutable('2026-11-01'),
            fechaSalida: new DateTimeImmutable('2026-11-04'),
            importe: '300.00',
            notas: null,
        );

        $reserva = $this->service->crear($dto);

        $this->assertSame(1, $reserva->id);
        $this->assertSame(EstadoReserva::Pendiente, $reserva->estado);

        $eventos = $this->eventoRepo->listarPorReserva(1);
        $this->assertCount(1, $eventos);
        $this->assertSame(ReservaEvento::TIPO_CREADA, $eventos[0]->tipo);
    }

    public function testCambiarEstadoRegistraEventoHistorial(): void
    {
        $dto = new NuevaReserva(
            nombreHuesped: 'David Bisbal',
            emailHuesped: 'david@example.com',
            nombreAlojamiento: 'Hotel Almería',
            fechaEntrada: new DateTimeImmutable('2026-11-01'),
            fechaSalida: new DateTimeImmutable('2026-11-04'),
            importe: '300.00',
            notas: null,
        );

        $reserva = $this->service->crear($dto);
        $actualizada = $this->service->cambiarEstado((int) $reserva->id, EstadoReserva::Confirmada);

        $this->assertSame(EstadoReserva::Confirmada, $actualizada->estado);

        $eventos = $this->eventoRepo->listarPorReserva((int) $reserva->id);
        $this->assertCount(2, $eventos);
        $this->assertSame(ReservaEvento::TIPO_CREADA, $eventos[0]->tipo);
        $this->assertSame('confirmada', $eventos[1]->tipo);
    }

    public function testObtenerReservaInexistenteLanzaNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->obtener(999);
    }
}

/**
 * Repositorio de reservas en memoria para tests unitarios.
 */
final class InMemoryReservaRepository implements ReservaRepository
{
    /** @var array<int, Reserva> */
    private array $items = [];
    private int $autoIncrement = 1;

    public function buscar(FiltroReservas $filtro): ResultadoPaginado
    {
        return new ResultadoPaginado(array_values($this->items), count($this->items), 1, 20);
    }

    public function buscarPorId(int $id): ?Reserva
    {
        return $this->items[$id] ?? null;
    }

    public function insertar(Reserva $reserva): int
    {
        $id = $this->autoIncrement++;
        $guardada = new Reserva(
            id: $id,
            nombreHuesped: $reserva->nombreHuesped,
            emailHuesped: $reserva->emailHuesped,
            nombreAlojamiento: $reserva->nombreAlojamiento,
            fechaEntrada: $reserva->fechaEntrada,
            fechaSalida: $reserva->fechaSalida,
            estado: $reserva->estado,
            importe: $reserva->importe,
            notas: $reserva->notas,
            fechaCreacion: $reserva->fechaCreacion,
        );
        $this->items[$id] = $guardada;

        return $id;
    }

    public function actualizarEstado(int $id, EstadoReserva $actual, EstadoReserva $nuevo): bool
    {
        if (!isset($this->items[$id]) || $this->items[$id]->estado !== $actual) {
            return false;
        }

        $reserva = $this->items[$id];
        $this->items[$id] = new Reserva(
            id: $reserva->id,
            nombreHuesped: $reserva->nombreHuesped,
            emailHuesped: $reserva->emailHuesped,
            nombreAlojamiento: $reserva->nombreAlojamiento,
            fechaEntrada: $reserva->fechaEntrada,
            fechaSalida: $reserva->fechaSalida,
            estado: $nuevo,
            importe: $reserva->importe,
            notas: $reserva->notas,
            fechaCreacion: $reserva->fechaCreacion,
        );

        return true;
    }
}

/**
 * Repositorio de eventos en memoria para tests unitarios.
 */
final class InMemoryEventoRepository implements ReservaEventoRepository
{
    /** @var array<int, list<ReservaEvento>> */
    private array $eventos = [];
    private int $autoIncrement = 1;

    public function registrar(ReservaEvento $evento): void
    {
        $id = $this->autoIncrement++;
        $guardado = new ReservaEvento(
            $id,
            $evento->reservaId,
            $evento->tipo,
            $evento->descripcion,
            $evento->fechaCreacion,
        );
        $this->eventos[$evento->reservaId][] = $guardado;
    }

    public function listarPorReserva(int $reservaId): array
    {
        return $this->eventos[$reservaId] ?? [];
    }
}
