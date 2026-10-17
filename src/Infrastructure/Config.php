<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Configuración de la aplicación.
 *
 * Lee un fichero .env sencillo (CLAVE=valor) sin dependencias externas.
 * Las variables de entorno reales del sistema tienen prioridad sobre el
 * fichero, para poder configurar el hosting sin subir secretos al repo.
 */
final class Config
{
    /** @param array<string, string> $values */
    private function __construct(private readonly array $values)
    {
    }

    public static function load(string $envFile): self
    {
        $values = is_readable($envFile) ? self::parse((string) file_get_contents($envFile)) : [];

        foreach (array_keys($values) as $key) {
            $real = getenv($key);
            if ($real !== false) {
                $values[$key] = $real;
            }
        }

        return new self($values);
    }

    /** @param array<string, string> $values */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        $real = getenv($key);

        return $real !== false ? $real : $default;
    }

    public function require(string $key): string
    {
        $value = $this->get($key);
        if ($value === null || $value === '') {
            throw new \RuntimeException(sprintf('Falta la variable de configuración "%s".', $key));
        }

        return $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public function isProduction(): bool
    {
        return $this->get('APP_ENV', 'prod') === 'prod';
    }

    public function isDebug(): bool
    {
        // Nunca se activa el modo debug en producción, aunque se configure por error.
        return !$this->isProduction() && $this->bool('APP_DEBUG');
    }

    /** @return array<string, string> */
    private static function parse(string $content): array
    {
        $values = [];

        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));

            if (preg_match('/^([\'"])(.*)\1$/', $value, $m) === 1) {
                $value = $m[2];
            }

            $values[$key] = $value;
        }

        return $values;
    }
}
