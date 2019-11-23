<?php

declare(strict_types=1);

namespace Antidot\React\Container;

use Psr\Container\ContainerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;
use React\Socket\Server;

class SocketServerFactory
{
    public function __invoke(ContainerInterface $container): ServerInterface
    {
        $config = $container->get('config')['react_http_server'];

        return new Server($config['uri'], $container->get(LoopInterface::class));
    }
}
