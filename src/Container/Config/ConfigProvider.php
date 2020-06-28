<?php

declare(strict_types=1);

namespace Antidot\React\Container\Config;

use Antidot\Application\Http\Application;
use Antidot\Application\Http\Middleware\Pipeline;
use Antidot\Application\Http\Router;
use Antidot\React\Container\ApplicationFactory;
use Antidot\React\Container\EmitterFactory;
use Antidot\React\Container\LoopFactory;
use Antidot\React\Container\ReactHttpServerFactory;
use Antidot\React\Container\SocketServerFactory;
use Antidot\React\MiddlewarePipeline;
use Antidot\React\ReactHttpServer;
use Antidot\React\Router\ReactFastRouter;
use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'services' => [
                Pipeline::class => MiddlewarePipeline::class,
                Router::class => ReactFastRouter::class,
            ],
            'factories' => [
                Application::class => ApplicationFactory::class,
                ServerInterface::class => SocketServerFactory::class,
                EmitterStack::class => EmitterFactory::class,
                LoopInterface::class => LoopFactory::class,
                ReactHttpServer::class => ReactHttpServerFactory::class,
            ],
            'console' => [
                'commands' => [
                    ReactHttpServer::NAME => ReactHttpServer::class,
                ]
            ],
            'react_http_server' => [
                'uri' => '0.0.0.0:8080',
                'container_path' => 'config/container.php',
                'router_path' => 'router/routes.php',
                'middleware_path' => 'router/middleware.php',
            ],
        ];
    }
}
