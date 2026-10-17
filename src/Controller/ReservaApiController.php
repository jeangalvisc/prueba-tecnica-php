<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Reserva;
use App\Domain\ReservaEvento;
use App\Http\Request;
use App\Http\Response;
use App\Service\ReservaService;
use App\Validation\FiltroValidator;
use App\Validation\ReservaValidator;

/**
 * Endpoints REST de reservas. Capa fina: traduce HTTP <-> casos de uso.
 *
 *   GET    /api/reservas              listado con filtros
 *   GET    /api/reservas/{id}         detalle + historial
 *   POST   /api/reservas              crear
 *   PATCH  /api/reservas/{id}/estado  confirmar / cancelar
 */
final class ReservaApiController
{
    public function __construct(
        private readonly ReservaService $service,
        private readonly ReservaValidator $validator,
        private readonly FiltroValidator $filtroValidator,
    ) {
    }

    public function listar(Request $request): Response
    {
        $filtro = $this->filtroValidator->desdeQuery($request->query());
        $resultado = $this->service->listar($filtro);

        return Response::json([
            'data' => array_map(static fn (Reserva $r): array => $r->toArray(), $resultado->items),
            'meta' => $resultado->meta(),
        ]);
    }

    /** @param array<string, string> $params */
    public function ver(Request $request, array $params): Response
    {
        $id = (int) $params['id'];

        return Response::json(['data' => $this->detalle($this->service->obtener($id))]);
    }

    public function crear(Request $request): Response
    {
        $datos = $this->validator->validarCreacion($request->json());
        $reserva = $this->service->crear($datos);

        return Response::json(
            ['data' => $this->detalle($reserva)],
            201,
            ['Location' => '/api/reservas/' . $reserva->id],
        );
    }

    /** @param array<string, string> $params */
    public function cambiarEstado(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $estado = $this->validator->validarCambioEstado($request->json());
        $reserva = $this->service->cambiarEstado($id, $estado);

        return Response::json(['data' => $this->detalle($reserva)]);
    }

    /** @return array<string, mixed> */
    private function detalle(Reserva $reserva): array
    {
        $eventos = $this->service->historial((int) $reserva->id);

        return $reserva->toArray() + [
            'eventos' => array_map(static fn (ReservaEvento $e): array => $e->toArray(), $eventos),
        ];
    }
}
