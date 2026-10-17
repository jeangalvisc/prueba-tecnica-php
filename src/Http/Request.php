<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Petición HTTP inmutable, construida a partir de las superglobales.
 *
 * Centraliza la lectura (y el saneado básico) de todo lo que llega de fuera:
 * método, ruta, query string, cabeceras y cuerpo JSON.
 */
final class Request
{
    /** Tamaño máximo del cuerpo aceptado (16 KB sobra para una reserva). */
    public const MAX_BODY_BYTES = 16_384;

    private const JSON_MAX_DEPTH = 16;

    /**
     * @param array<string, mixed>  $query
     * @param array<string, string> $headers cabeceras con el nombre en minúsculas
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $headers = [],
        private readonly string $body = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headers[strtolower(str_replace('_', '-', $key))] = $value;
            }
        }

        $body = '';
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            // Se lee como máximo un byte más del límite: suficiente para saber
            // si se ha superado sin cargar en memoria cuerpos enormes.
            $body = (string) file_get_contents('php://input', false, null, 0, self::MAX_BODY_BYTES + 1);
        }

        return new self($method, $path, $_GET, $headers, $body);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /** @return array<string, mixed> */
    public function query(): array
    {
        return $this->query;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function isApi(): bool
    {
        return $this->path === '/api' || str_starts_with($this->path, '/api/');
    }

    public function isWrite(): bool
    {
        return in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    /**
     * Devuelve el cuerpo decodificado como objeto JSON (array asociativo).
     *
     * @return array<string, mixed>
     *
     * @throws HttpException 415 / 413 / 400
     */
    public function json(): array
    {
        $contentType = strtolower(trim(explode(';', (string) $this->header('content-type'))[0]));
        if ($contentType !== 'application/json') {
            throw new HttpException(415, 'El cuerpo debe enviarse como application/json.');
        }

        if (strlen($this->body) > self::MAX_BODY_BYTES) {
            throw new HttpException(413, 'El cuerpo de la petición es demasiado grande.');
        }

        if (trim($this->body) === '') {
            throw new HttpException(400, 'El cuerpo de la petición está vacío.');
        }

        try {
            $data = json_decode($this->body, true, self::JSON_MAX_DEPTH, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException) {
            throw new HttpException(400, 'El cuerpo no es un JSON válido.');
        }

        if (!is_array($data) || ($data !== [] && array_is_list($data))) {
            throw new HttpException(400, 'Se esperaba un objeto JSON.');
        }

        return $data;
    }
}
