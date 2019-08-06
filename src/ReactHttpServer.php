<?php

declare(strict_types=1);

namespace Antidot\React;

use Antidot\Application\Http\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory;
use React\Http\Server;
use React\Socket\Server as Socketserver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ReactHttpServer extends Command
{
    private $config;

    public function __construct(array $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    protected function configure(): void
    {
        $this->setName('react-server:http');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loop = Factory::create();
        $middleware = require $this->config['middleware_path'];
        $routes = require $this->config['router_path'];
        $server = new Server(function (ServerRequestInterface $request) use ($middleware, $routes) {
            return $this->handle($request, $this->config['container_path'], $middleware, $routes);
        });

        $socket = new Socketserver($this->config['uri'], $loop);
        $server->listen($socket);

        $loop->run();
    }

    private function handle(
        ServerRequestInterface $request,
        string $containerPath,
        callable $middleware,
        callable $routes
    ): ResponseInterface {
        try {
            $container = require $containerPath;
            /** @var RequestHandler $application */
            $application = $container->get(Application::class);
            $middleware($application, $container);
            $routes($application, $container);

            return $application->handle($request);
        } catch (Throwable $exception) {
            \dump($exception);
            throw $exception;
        }
    }
}
