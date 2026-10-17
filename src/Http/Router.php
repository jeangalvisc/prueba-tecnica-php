<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Router mínimo basado en expresiones regulares.
 *
 * Los parámetros de ruta ({id}) solo aceptan enteros positivos, de modo que
 * cualquier otra cosa ni siquiera llega al controlador.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: callable(Request, array<string, string>): Response}> */
    private array $routes = [];

    /** @param callable(Request, array<string, string>): Response $handler */
    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    /** @param callable(Request, array<string, string>): Response $handler */
    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /** @param callable(Request, array<string, string>): Response $handler */
    public function patch(string $pattern, callable $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    /** @param callable(Request, array<string, string>): Response $handler */
    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[1-9][0-9]{0,9})', $pattern);

        $this->routes[] = [
            'method'  => strtoupper($method),
            'regex'   => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method() === 'HEAD' ? 'GET' : $request->method();
        $allowed = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path(), $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return ($route['handler'])($request, $params);
        }

        if ($allowed !== []) {
            throw new HttpException(405, 'Método no permitido.', ['Allow' => implode(', ', array_unique($allowed))]);
        }

        throw new HttpException(404, 'Recurso no encontrado.');
    }
}
