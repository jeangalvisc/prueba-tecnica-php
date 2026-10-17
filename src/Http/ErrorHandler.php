<?php

declare(strict_types=1);

namespace App\Http;

use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use Closure;
use Throwable;

/**
 * Manejo de errores centralizado.
 *
 * Traduce cualquier excepción a una respuesta coherente (JSON para la API,
 * HTML para las páginas) y registra los errores inesperados. En producción
 * nunca se exponen mensajes internos, trazas ni SQL al cliente.
 */
final class ErrorHandler
{
    /**
     * @param (Closure(int, string): string)|null $htmlRenderer pinta la página de error
     */
    public function __construct(
        private readonly bool $debug,
        private readonly string $logFile,
        private ?Closure $htmlRenderer = null,
    ) {
    }

    /** @param Closure(int, string): string $renderer */
    public function setHtmlRenderer(Closure $renderer): void
    {
        $this->htmlRenderer = $renderer;
    }

    /**
     * Convierte warnings/notices en excepciones y captura errores fatales,
     * para que nada se escape sin pasar por aquí.
     */
    public function register(): void
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if ((error_reporting() & $severity) === 0) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                return;
            }

            $this->log(sprintf('FATAL %s in %s:%d', $error['message'], $error['file'], $error['line']));

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo '{"error":{"message":"Error interno del servidor."}}';
            }
        });
    }

    public function handle(Throwable $e, Request $request): Response
    {
        [$status, $message, $headers] = $this->map($e);

        if ($status >= 500) {
            $this->log($this->describe($e, $request));
        }

        if ($request->isApi()) {
            $error = ['message' => $message];

            if ($e instanceof ValidationException) {
                $error['fields'] = $e->errors();
            }

            if ($this->debug && $status >= 500) {
                $error['debug'] = [
                    'exception' => $e::class,
                    'message'   => $e->getMessage(),
                    'at'        => $e->getFile() . ':' . $e->getLine(),
                ];
            }

            return Response::json(['error' => $error], $status, $headers);
        }

        return Response::html($this->renderHtml($status, $message, $e), $status, $headers);
    }

    /** @return array{int, string, array<string, string>} */
    private function map(Throwable $e): array
    {
        return match (true) {
            $e instanceof ValidationException => [400, $e->getMessage(), []],
            $e instanceof NotFoundException   => [404, $e->getMessage(), []],
            $e instanceof ConflictException   => [409, $e->getMessage(), []],
            $e instanceof HttpException       => [$e->status(), $e->getMessage(), $e->headers()],
            default                           => [500, 'Error interno del servidor.', []],
        };
    }

    private function renderHtml(int $status, string $message, Throwable $e): string
    {
        if ($this->htmlRenderer !== null) {
            try {
                return ($this->htmlRenderer)($status, $message);
            } catch (Throwable $renderError) {
                $this->log('Error al renderizar la página de error: ' . $renderError->getMessage());
            }
        }

        $safe = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $detail = $this->debug && $status >= 500
            ? '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>'
            : '';

        return "<!doctype html><html lang=\"es\"><meta charset=\"utf-8\"><title>Error {$status}</title>"
            . "<h1>Error {$status}</h1><p>{$safe}</p>{$detail}</html>";
    }

    private function describe(Throwable $e, Request $request): string
    {
        return sprintf(
            "%s %s -> %s: %s at %s:%d\n%s",
            $request->method(),
            $request->path(),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
        );
    }

    private function log(string $message): void
    {
        $line = sprintf("[%s] %s\n", date('c'), $message);
        $dir = dirname($this->logFile);

        if ((is_dir($dir) || @mkdir($dir, 0775, true)) && @error_log($line, 3, $this->logFile)) {
            return;
        }

        error_log(rtrim($line));
    }
}
