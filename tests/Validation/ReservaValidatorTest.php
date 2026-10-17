<?php

declare(strict_types=1);

namespace Tests\Validation;

use App\Domain\EstadoReserva;
use App\Exception\ValidationException;
use App\Infrastructure\Clock;
use App\Validation\ReservaValidator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ReservaValidatorTest extends TestCase
{
    private Clock $clock;
    private ReservaValidator $validator;

    protected function setUp(): void
    {
        $this->clock = new class implements Clock {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-10-17 10:00:00');
            }
        };

        $this->validator = new ReservaValidator($this->clock);
    }

    public function testValidarCreacionExitosa(): void
    {
        $payload = [
            'nombre_huesped'     => 'Carlos Sainz',
            'email_huesped'      => 'carlos@example.com',
            'nombre_alojamiento' => 'Hotel Gran Vía',
            'fecha_entrada'      => '2026-10-20',
            'fecha_salida'       => '2026-10-25',
            'importe'            => '520.50',
            'notas'              => 'Cama de matrimonio',
        ];

        $dto = $this->validator->validarCreacion($payload);

        $this->assertSame('Carlos Sainz', $dto->nombreHuesped);
        $this->assertSame('carlos@example.com', $dto->emailHuesped);
        $this->assertSame('Hotel Gran Vía', $dto->nombreAlojamiento);
        $this->assertSame('2026-10-20', $dto->fechaEntrada->format('Y-m-d'));
        $this->assertSame('2026-10-25', $dto->fechaSalida->format('Y-m-d'));
        $this->assertSame('520.50', $dto->importe);
        $this->assertSame('Cama de matrimonio', $dto->notas);
    }

    public function testCamposObligatoriosFaltantes(): void
    {
        try {
            $this->validator->validarCreacion([]);
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $errores = $e->errors();
            $this->assertArrayHasKey('nombre_huesped', $errores);
            $this->assertArrayHasKey('email_huesped', $errores);
            $this->assertArrayHasKey('nombre_alojamiento', $errores);
            $this->assertArrayHasKey('fecha_entrada', $errores);
            $this->assertArrayHasKey('fecha_salida', $errores);
            $this->assertArrayHasKey('importe', $errores);
        }
    }

    public function testFechaEntradaEnElPasado(): void
    {
        $payload = [
            'nombre_huesped'     => 'Carlos Sainz',
            'email_huesped'      => 'carlos@example.com',
            'nombre_alojamiento' => 'Hotel Gran Vía',
            'fecha_entrada'      => '2026-10-10', // Pasado con respecto al reloj del test (2026-10-17)
            'fecha_salida'       => '2026-10-20',
            'importe'            => '520.50',
        ];

        try {
            $this->validator->validarCreacion($payload);
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fecha_entrada', $e->errors());
            $this->assertStringContainsString('anterior a hoy', $e->errors()['fecha_entrada']);
        }
    }

    public function testFechaSalidaAnteriorOIgualAEntrada(): void
    {
        $payload = [
            'nombre_huesped'     => 'Carlos Sainz',
            'email_huesped'      => 'carlos@example.com',
            'nombre_alojamiento' => 'Hotel Gran Vía',
            'fecha_entrada'      => '2026-10-20',
            'fecha_salida'       => '2026-10-20',
            'importe'            => '520.50',
        ];

        try {
            $this->validator->validarCreacion($payload);
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fecha_salida', $e->errors());
            $this->assertStringContainsString('posterior a la de entrada', $e->errors()['fecha_salida']);
        }
    }

    public function testEmailInvalido(): void
    {
        $payload = [
            'nombre_huesped'     => 'Carlos Sainz',
            'email_huesped'      => 'email-invalido',
            'nombre_alojamiento' => 'Hotel Gran Vía',
            'fecha_entrada'      => '2026-10-20',
            'fecha_salida'       => '2026-10-22',
            'importe'            => '150.00',
        ];

        try {
            $this->validator->validarCreacion($payload);
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email_huesped', $e->errors());
        }
    }

    public function testImporteInvalidoONegativo(): void
    {
        $payload = [
            'nombre_huesped'     => 'Carlos Sainz',
            'email_huesped'      => 'carlos@example.com',
            'nombre_alojamiento' => 'Hotel Gran Vía',
            'fecha_entrada'      => '2026-10-20',
            'fecha_salida'       => '2026-10-22',
            'importe'            => '-50.00',
        ];

        try {
            $this->validator->validarCreacion($payload);
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('importe', $e->errors());
        }
    }

    public function testValidarCambioEstadoPermitido(): void
    {
        $estado = $this->validator->validarCambioEstado(['estado' => 'confirmada']);
        $this->assertSame(EstadoReserva::Confirmada, $estado);

        $estado = $this->validator->validarCambioEstado(['estado' => 'cancelada']);
        $this->assertSame(EstadoReserva::Cancelada, $estado);
    }

    public function testValidarCambioEstadoInvalido(): void
    {
        $this->expectException(ValidationException::class);
        $this->validator->validarCambioEstado(['estado' => 'desconocido']);
    }
}
