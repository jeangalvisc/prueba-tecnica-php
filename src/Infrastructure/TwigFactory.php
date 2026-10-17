<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Factoría para configurar Twig con autoescaping estricto y caché opcional.
 */
final class TwigFactory
{
    public static function create(string $templatesDir, Config $config, string $cacheDir): Environment
    {
        $loader = new FilesystemLoader($templatesDir);

        $isDebug = $config->isDebug();
        $options = [
            'debug'            => $isDebug,
            'autoescape'       => 'html',
            'strict_variables' => $isDebug,
            'cache'            => $config->isProduction() ? $cacheDir : false,
        ];

        $twig = new Environment($loader, $options);

        if ($isDebug) {
            $twig->addExtension(new DebugExtension());
        }

        return $twig;
    }
}
