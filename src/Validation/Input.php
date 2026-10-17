<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exception\ValidationException;
use DateTimeImmutable;

/**
 * Lector tipado de datos de entrada que acumula errores por campo.
 *
 * No se fía de nada: comprueba tipos (un campo puede llegar como array,
 * número o booleano aunque se espere texto), codificación UTF-8, caracteres
 * de control, longitudes y formatos. Cada método devuelve el valor ya
 * normalizado o null si falta / no es válido (y en ese caso anota el error).
 */
final class Input
{
    /** @var array<string, string> */
    private array $errores = [];

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data)
    {
    }

    public function texto(
        string $campo,
        bool $obligatorio,
        int $max,
        int $min = 0,
        bool $multilinea = false,
    ): ?string {
        $valor = $this->data[$campo] ?? null;

        if ($valor === null || (is_string($valor) && trim($valor) === '')) {
            if ($obligatorio) {
                $this->error($campo, 'Este campo es obligatorio.');
            }

            return null;
        }

        if (!is_string($valor)) {
            $this->error($campo, 'Debe ser un texto.');

            return null;
        }

        if (!mb_check_encoding($valor, 'UTF-8')) {
            $this->error($campo, 'Contiene caracteres no válidos.');

            return null;
        }

        $valor = trim($valor);
        // Caracteres de control: nunca permitidos (salvo saltos de línea y tabulador en multilínea).
        $control = $multilinea ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u';
        if (preg_match($control, $valor) === 1) {
            $this->error($campo, 'Contiene caracteres no permitidos.');

            return null;
        }

        $longitud = mb_strlen($valor, 'UTF-8');
        if ($longitud < $min) {
            $this->error($campo, sprintf('Debe tener al menos %d caracteres.', $min));

            return null;
        }
        if ($longitud > $max) {
            $this->error($campo, sprintf('No puede superar los %d caracteres.', $max));

            return null;
        }

        return $valor;
    }

    public function email(string $campo, bool $obligatorio = true): ?string
    {
        $valor = $this->texto($campo, $obligatorio, 254);
        if ($valor === null) {
            return null;
        }

        if (filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
            $this->error($campo, 'El email no es válido.');

            return null;
        }

        return $valor;
    }

    /** Fecha en formato estricto AAAA-MM-DD que además exista en el calendario. */
    public function fecha(string $campo, bool $obligatorio): ?DateTimeImmutable
    {
        $valor = $this->texto($campo, $obligatorio, 10);
        if ($valor === null) {
            return null;
        }

        $fecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $valor)
            : false;

        // Si PHP "corrige" la fecha (2026-02-30 -> 2026-03-02) es que no existía.
        if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
            $this->error($campo, 'Fecha no válida (formato AAAA-MM-DD).');

            return null;
        }

        return $fecha;
    }

    /**
     * Importe positivo con hasta 2 decimales. Se devuelve como string
     * normalizado ("645.50") para no perder precisión con floats.
     */
    public function importe(string $campo, string $maximo = '99999999.99'): ?string
    {
        $valor = $this->data[$campo] ?? null;

        if ($valor === null || $valor === '') {
            $this->error($campo, 'Este campo es obligatorio.');

            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            $valor = (string) $valor;
        }

        if (!is_string($valor) || preg_match('/^(\d{1,8})(?:\.(\d{1,2}))?$/', trim($valor), $m) !== 1) {
            $this->error($campo, 'Debe ser un importe válido, con hasta 2 decimales (p. ej. 120.50).');

            return null;
        }

        $normalizado = (ltrim($m[1], '0') ?: '0') . '.' . str_pad($m[2] ?? '', 2, '0');

        if (self::compararDecimal($normalizado, '0.00') <= 0) {
            $this->error($campo, 'El importe debe ser mayor que 0.');

            return null;
        }

        if (self::compararDecimal($normalizado, $maximo) > 0) {
            $this->error($campo, sprintf('El importe no puede superar %s.', $maximo));

            return null;
        }

        return $normalizado;
    }

    /**
     * @param list<string> $permitidos
     */
    public function opcion(string $campo, array $permitidos, bool $obligatorio): ?string
    {
        $valor = $this->texto($campo, $obligatorio, 50);
        if ($valor === null) {
            return null;
        }

        if (!in_array($valor, $permitidos, true)) {
            $this->error($campo, sprintf('Valor no permitido. Opciones: %s.', implode(', ', $permitidos)));

            return null;
        }

        return $valor;
    }

    public function entero(string $campo, int $defecto, int $min, int $max): int
    {
        $valor = $this->data[$campo] ?? null;
        if ($valor === null || $valor === '') {
            return $defecto;
        }

        $entero = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
        if ($entero === false || is_bool($valor)) {
            $this->error($campo, sprintf('Debe ser un número entero entre %d y %d.', $min, $max));

            return $defecto;
        }

        return $entero;
    }

    /** Anota un error (se conserva el primero de cada campo). */
    public function error(string $campo, string $mensaje): void
    {
        $this->errores[$campo] ??= $mensaje;
    }

    public function tieneError(string $campo): bool
    {
        return isset($this->errores[$campo]);
    }

    /** @return array<string, string> */
    public function errores(): array
    {
        return $this->errores;
    }

    /** @throws ValidationException */
    public function lanzarSiHayErrores(): void
    {
        if ($this->errores !== []) {
            throw new ValidationException($this->errores);
        }
    }

    /**
     * Compara dos decimales positivos normalizados con 2 cifras ("123.45")
     * sin usar floats ni depender de la extensión bcmath.
     */
    private static function compararDecimal(string $a, string $b): int
    {
        [$aEntero, $aDecimal] = explode('.', $a);
        [$bEntero, $bDecimal] = explode('.', $b);

        return [strlen($aEntero), $aEntero, $aDecimal] <=> [strlen($bEntero), $bEntero, $bDecimal];
    }
}
