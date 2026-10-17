<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\EstadoReserva;
use App\Domain\FiltroReservas;
use App\Exception\ValidationException;

/**
 * Valida los filtros del listado que llegan por query string.
 * Los campos vacíos se interpretan como "sin filtro".
 */
final class FiltroValidator
{
    /**
     * @param array<string, mixed> $query
     *
     * @throws ValidationException
     */
    public function desdeQuery(array $query): FiltroReservas
    {
        $in = new Input($query);

        $estado = $in->opcion('estado', EstadoReserva::valores(), obligatorio: false);
        $desde = $in->fecha('desde', obligatorio: false);
        $hasta = $in->fecha('hasta', obligatorio: false);
        $busqueda = $in->texto('q', obligatorio: false, max: 100);
        $pagina = $in->entero('pagina', 1, 1, 100_000);
        $porPagina = $in->entero('por_pagina', FiltroReservas::POR_PAGINA_DEFECTO, 1, FiltroReservas::POR_PAGINA_MAX);

        if ($desde !== null && $hasta !== null && $hasta < $desde) {
            $in->error('hasta', 'La fecha "hasta" no puede ser anterior a "desde".');
        }

        $in->lanzarSiHayErrores();

        return new FiltroReservas(
            estado: $estado !== null ? EstadoReserva::from($estado) : null,
            desde: $desde,
            hasta: $hasta,
            busqueda: $busqueda,
            pagina: $pagina,
            porPagina: $porPagina,
        );
    }
}
