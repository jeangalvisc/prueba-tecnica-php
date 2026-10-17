<?php

declare(strict_types=1);

namespace App;

use App\Controller\ReservaApiController;
use App\Controller\ReservaPageController;
use App\Http\ErrorHandler;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Http\SecurityHeaders;
use App\Infrastructure\Clock;
use App\Infrastructure\Config;
use App\Infrastructure\Database;
use App\Infrastructure\SystemClock;
use App\Infrastructure\TwigFactory;
use App\Repository\Pdo\PdoReservaEventoRepository;
use App\Repository\Pdo\PdoReservaRepository;
use App\Repository\Pdo\PdoTransactionManager;
use App\Service\ReservaService;
use App\Validation\FiltroValidator;
use App\Validation\ReservaValidator;
use PDO;
use Throwable;
use Twig\Environment;

/**
 * Punto de composición de la aplicación: crea las dependencias (inyección
 * manual, sin contenedor), define las rutas y atiende cada petición.
 * Las dependencias se crean bajo demanda: una petición que no necesita BD
 * no abre conexión.
 */
final class Kernel
{
    private ?PDO $pdo = null;
    private ?ReservaService $reservaService = null;
    private ?Environment $twig = null;
    private readonly Clock $clock;
    private readonly Router $router;

    public function __construct(
        private readonly Config $config,
        private readonly ErrorHandler $errorHandler,
        private readonly string $rootDir,
    ) {
        $this->clock = new SystemClock();
        $this->router = $this->routes();

        $this->errorHandler->setHtmlRenderer(function (int $status, string $message): string {
            return $this->twig()->render('error.html.twig', [
                'status'  => $status,
                'message' => $message,
            ]);
        });
    }

    public static function boot(string $rootDir): self
    {
        date_default_timezone_set('Europe/Madrid');
        mb_internal_encoding('UTF-8');

        $config = Config::load($rootDir . '/.env');
        $errorHandler = new ErrorHandler($config->isDebug(), $rootDir . '/var/log/app.log');
        $errorHandler->register();

        return new self($config, $errorHandler, $rootDir);
    }

    public function handle(Request $request): Response
    {
        try {
            SecurityHeaders::assertSameOrigin($request);
            $response = $this->router->dispatch($request);
        } catch (Throwable $e) {
            $response = $this->errorHandler->handle($e, $request);
        }

        return SecurityHeaders::apply($response);
    }

    private function routes(): Router
    {
        $router = new Router();

        // API REST
        $api = fn (): ReservaApiController => new ReservaApiController(
            $this->reservaService(),
            new ReservaValidator($this->clock),
            new FiltroValidator(),
        );

        $router->get('/api/reservas', fn (Request $r) => $api()->listar($r));
        $router->post('/api/reservas', fn (Request $r) => $api()->crear($r));
        $router->get('/api/reservas/{id}', fn (Request $r, array $p) => $api()->ver($r, $p));
        $router->patch('/api/reservas/{id}/estado', fn (Request $r, array $p) => $api()->cambiarEstado($r, $p));

        // Páginas web (Twig)
        $pages = fn (): ReservaPageController => new ReservaPageController(
            $this->reservaService(),
            new FiltroValidator(),
            $this->twig(),
        );

        $router->get('/', fn (Request $r) => $pages()->listado($r));
        $router->get('/reservas/{id}', fn (Request $r, array $p) => $pages()->detalle($r, $p));

        return $router;
    }

    private function reservaService(): ReservaService
    {
        return $this->reservaService ??= new ReservaService(
            new PdoReservaRepository($this->pdo()),
            new PdoReservaEventoRepository($this->pdo()),
            new PdoTransactionManager($this->pdo()),
            $this->clock,
        );
    }

    private function twig(): Environment
    {
        return $this->twig ??= TwigFactory::create(
            $this->rootDir . '/templates',
            $this->config,
            $this->rootDir . '/var/cache/twig',
        );
    }

    private function pdo(): PDO
    {
        return $this->pdo ??= Database::connect($this->config);
    }
}
