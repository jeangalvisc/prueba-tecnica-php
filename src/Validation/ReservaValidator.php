<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\EstadoReserva;
use App\Domain\NuevaReserva;
use App\Exception\ValidationException;
use App\Infrastructure\Clock;

/**
 * Validación de los datos de entrada de la API de reservas.
 * Convierte el array recibido en objetos del dominio ya válidos.
 */
final class ReservaValidator
{
    public const MAX_NOCHES = 365;

    /** Letras (cualquier idioma), espacios, apóstrofos, puntos y guiones. */
    private const PATRON_NOMBRE = "/^[\\p{L}\\p{M}][\\p{L}\\p{M} '’.\\-]*$/u";

    public function __construct(private readonly Clock $clock)
    {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function validarCreacion(array $data): NuevaReserva
    {
        $in = new Input($data);

        $nombre = $in->texto('nombre_huesped', obligatorio: true, max: 120, min: 2);
        if ($nombre !== null && preg_match(self::PATRON_NOMBRE, $nombre) !== 1) {
            $in->error('nombre_huesped', 'El nombre solo puede contener letras, espacios, apóstrofos, puntos y guiones.');
        }

        $email = $in->email('email_huesped');
        $alojamiento = $in->texto('nombre_alojamiento', obligatorio: true, max: 150, min: 2);
        $entrada = $in->fecha('fecha_entrada', obligatorio: true);
        $salida = $in->fecha('fecha_salida', obligatorio: true);
        $importe = $in->importe('importe');
        $notas = $in->texto('notas', obligatorio: false, max: 1000, multilinea: true);

        if ($entrada !== null && $salida !== null) {
            $hoy = $this->clock->now()->setTime(0, 0);

            if ($entrada < $hoy) {
                $in->error('fecha_entrada', 'La fecha de entrada no puede ser anterior a hoy.');
            }

            if ($salida <= $entrada) {
                $in->error('fecha_salida', 'La fecha de salida debe ser posterior a la de entrada.');
            } elseif ($entrada->diff($salida)->days > self::MAX_NOCHES) {
                $in->error('fecha_salida', sprintf('La estancia no puede superar %d noches.', self::MAX_NOCHES));
            }
        }

        $in->lanzarSiHayErrores();

        // Llegados aquí, todos los obligatorios tienen valor.
        assert($nombre !== null && $email !== null && $alojamiento !== null);
        assert($entrada !== null && $salida !== null && $importe !== null);

        return new NuevaReserva($nombre, $email, $alojamiento, $entrada, $salida, $importe, $notas);
    }

    /**
     * Solo se aceptan como destino los estados alcanzables desde la API.
     * Que la transición concreta sea válida lo decide el dominio.
     *
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function validarCambioEstado(array $data): EstadoReserva
    {
        $in = new Input($data);
        $estado = $in->opcion(
            'estado',
            [EstadoReserva::Confirmada->value, EstadoReserva::Cancelada->value],
            obligatorio: true,
        );
        $in->lanzarSiHayErrores();

        return EstadoReserva::from((string) $estado);
    }
}
