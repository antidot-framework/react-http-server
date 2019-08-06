<?php

declare(strict_types=1);

namespace Antidot\React\Container\Config;

use Antidot\Application\Http\Application;
use Antidot\React\Container\ApplicationFactory;
use Antidot\React\Container\ReactHttpServerFactory;
use Antidot\React\ReactHttpServer;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => [
                    Application::class => ApplicationFactory::class,
                    ReactHttpServer::class => ReactHttpServerFactory::class,
                ]
            ],
            'console' => [
                'commands' => [
                    'http:server' => ReactHttpServer::class,
                ]
            ],
            'react_http_server' => [
                'uri' => 8080,
                'container_path' => 'config/container.php',
                'router_path' => 'router/routes.php',
                'middleware_path' => 'router/middleware.php',
            ],
            'react_socket_server' => [
                'uri' => 1234,
            ],
        ];
    }
}
