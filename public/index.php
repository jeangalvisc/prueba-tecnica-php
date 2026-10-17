<?php

declare(strict_types=1);

/*
 * Front controller: todas las peticiones entran por aquí.
 * Solo el directorio public/ debe ser accesible desde la web.
 */

use App\Http\Request;
use App\Kernel;

// Servidor embebido de PHP (desarrollo): servir directamente los ficheros estáticos existentes.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $file = realpath(__DIR__ . (is_string($path) ? $path : '/'));
    if ($file !== false && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR) && $file !== __FILE__) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

header_remove('X-Powered-By');

Kernel::boot(dirname(__DIR__))
    ->handle(Request::fromGlobals())
    ->send();
