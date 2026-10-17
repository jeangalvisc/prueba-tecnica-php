<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

/**
 * Criterios (ya validados) para listar reservas. Todos son opcionales y combinables.
 *
 * El rango de fechas busca reservas que SOLAPAN con [desde, hasta]: es decir,
 * con alguna noche de estancia dentro del rango, que es lo útil en un alojamiento.
 */
final class FiltroReservas
{
    public const POR_PAGINA_DEFECTO = 20;
    public const POR_PAGINA_MAX = 100;

    public function __construct(
        public readonly ?EstadoReserva $estado = null,
        public readonly ?DateTimeImmutable $desde = null,
        public readonly ?DateTimeImmutable $hasta = null,
        public readonly ?string $busqueda = null,
        public readonly int $pagina = 1,
        public readonly int $porPagina = self::POR_PAGINA_DEFECTO,
    ) {
    }

    public function offset(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }

    /**
     * Valores tal y como se reciben en la query string (para rellenar el formulario).
     *
     * @return array{estado: string, desde: string, hasta: string, q: string}
     */
    public function toQuery(): array
    {
        return [
            'estado' => $this->estado?->value ?? '',
            'desde'  => $this->desde?->format('Y-m-d') ?? '',
            'hasta'  => $this->hasta?->format('Y-m-d') ?? '',
            'q'      => $this->busqueda ?? '',
        ];
    }
}
