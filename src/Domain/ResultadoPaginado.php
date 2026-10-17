<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Página de resultados del listado.
 */
final class ResultadoPaginado
{
    /** @param list<Reserva> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $pagina,
        public readonly int $porPagina,
    ) {
    }

    public function totalPaginas(): int
    {
        return max(1, (int) ceil($this->total / $this->porPagina));
    }

    /** @return array{total: int, pagina: int, por_pagina: int, total_paginas: int} */
    public function meta(): array
    {
        return [
            'total'         => $this->total,
            'pagina'        => $this->pagina,
            'por_pagina'    => $this->porPagina,
            'total_paginas' => $this->totalPaginas(),
        ];
    }
}
