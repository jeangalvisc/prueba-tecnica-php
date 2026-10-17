<?php

declare(strict_types=1);

namespace Tests\Validation;

use App\Domain\EstadoReserva;
use App\Exception\ValidationException;
use App\Validation\FiltroValidator;
use PHPUnit\Framework\TestCase;

final class FiltroValidatorTest extends TestCase
{
    private FiltroValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FiltroValidator();
    }

    public function testFiltroVacioDevuelveValoresPorDefecto(): void
    {
        $filtro = $this->validator->desdeQuery([]);

        $this->assertNull($filtro->estado);
        $this->assertNull($filtro->desde);
        $this->assertNull($filtro->hasta);
        $this->assertNull($filtro->busqueda);
        $this->assertSame(1, $filtro->pagina);
        $this->assertSame(20, $filtro->porPagina);
    }

    public function testFiltroConParametrosValidos(): void
    {
        $query = [
            'estado' => 'confirmada',
            'desde'  => '2026-11-01',
            'hasta'  => '2026-11-10',
            'q'      => 'García',
            'pagina' => '2',
        ];

        $filtro = $this->validator->desdeQuery($query);

        $this->assertSame(EstadoReserva::Confirmada, $filtro->estado);
        $this->assertSame('2026-11-01', $filtro->desde?->format('Y-m-d'));
        $this->assertSame('2026-11-10', $filtro->hasta?->format('Y-m-d'));
        $this->assertSame('García', $filtro->busqueda);
        $this->assertSame(2, $filtro->pagina);
        $this->assertSame(20, $filtro->offset());
    }

    public function testRangoFechasIncoherenteLanzaError(): void
    {
        $query = [
            'desde' => '2026-11-10',
            'hasta' => '2026-11-01',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->desdeQuery($query);
    }
}
