<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Cabeceras de seguridad comunes a todas las respuestas.
 */
final class SecurityHeaders
{
    private const CSP = "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; "
        . "connect-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; "
        . "form-action 'self'; frame-ancestors 'none'";

    public static function apply(Response $response): Response
    {
        $headers = [
            'X-Content-Type-Options'     => 'nosniff',
            'X-Frame-Options'            => 'DENY',
            'Referrer-Policy'            => 'same-origin',
            'Content-Security-Policy'    => self::CSP,
            'Permissions-Policy'         => 'camera=(), microphone=(), geolocation=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        foreach ($headers as $name => $value) {
            if ($response->header($name) === null) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    /**
     * Rechaza escrituras iniciadas desde otro origen (defensa en profundidad
     * frente a CSRF). Clientes sin navegador (curl, Postman) no envían
     * Origin ni Sec-Fetch-Site y no se ven afectados.
     *
     * @throws HttpException 403
     */
    public static function assertSameOrigin(Request $request): void
    {
        if (!$request->isWrite()) {
            return;
        }

        if ($request->header('sec-fetch-site') === 'cross-site') {
            throw new HttpException(403, 'Petición de origen cruzado no permitida.');
        }

        $origin = $request->header('origin');
        if ($origin === null || $origin === '' || $origin === 'null') {
            return;
        }

        $originHost = parse_url($origin, PHP_URL_HOST);
        $originPort = parse_url($origin, PHP_URL_PORT);
        $originAuthority = $originHost . ($originPort !== null ? ':' . $originPort : '');

        if (strcasecmp($originAuthority, (string) $request->header('host')) !== 0) {
            throw new HttpException(403, 'Petición de origen cruzado no permitida.');
        }
    }
}
