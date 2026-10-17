<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\EstadoReserva;
use App\Http\Request;
use App\Http\Response;
use App\Service\ReservaService;
use App\Validation\FiltroValidator;
use Twig\Environment;

/**
 * Controlador para las páginas HTML renderizadas con Twig en el servidor.
 */
final class ReservaPageController
{
    public function __construct(
        private readonly ReservaService $service,
        private readonly FiltroValidator $filtroValidator,
        private readonly Environment $twig,
    ) {
    }

    public function listado(Request $request): Response
    {
        $filtro = $this->filtroValidator->desdeQuery($request->query());
        $resultado = $this->service->listar($filtro);

        $html = $this->twig->render('reservas/listado.html.twig', [
            'reservas' => $resultado->items,
            'meta'     => $resultado->meta(),
            'filtros'  => $filtro->toQuery(),
            'estados'  => EstadoReserva::cases(),
        ]);

        return Response::html($html);
    }

    /** @param array<string, string> $params */
    public function detalle(Request $request, array $params): Response
    {
        $id = (int) $params['id'];
        $reserva = $this->service->obtener($id);
        $eventos = $this->service->historial($id);

        $html = $this->twig->render('reservas/detalle.html.twig', [
            'reserva' => $reserva,
            'eventos' => $eventos,
        ]);

        return Response::html($html);
    }
}
