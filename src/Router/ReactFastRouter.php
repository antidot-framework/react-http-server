<?php

namespace Antidot\React\Router;

use Antidot\Application\Http\Middleware\CallableMiddleware;
use Antidot\Application\Http\Middleware\PipedRouteMiddleware;
use Antidot\Application\Http\Route;
use Antidot\Application\Http\Router;
use Antidot\Container\MiddlewareFactory;
use Antidot\Container\RequestHandlerFactory;
use Antidot\React\MiddlewarePipeline;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;

class ReactFastRouter implements Router
{
    private RouteCollector $routeCollector;
    private MiddlewareFactory $middlewareFactory;
    private RequestHandlerFactory $requestHandlerFactory;
    private ?Dispatcher\GroupCountBased $dispatcher = null;
    private array $loadedMiddleware;
    private LoopInterface $loop;

    public function __construct(
        MiddlewareFactory $middlewareFactory,
        RequestHandlerFactory $requestHandlerFactory,
        LoopInterface $loop
    ) {
        $this->routeCollector = new RouteCollector(new Std(), new GroupCountBased());
        $this->middlewareFactory = $middlewareFactory;
        $this->requestHandlerFactory = $requestHandlerFactory;
        $this->loop = $loop;
        $this->loadedMiddleware = [];
    }

    public function append(Route $route): void
    {
        $this->routeCollector->addRoute($route->method(), $route->path(), $route->pipeline());
    }

    public function match(ServerRequestInterface $request): PipedRouteMiddleware
    {
        if (null === $this->dispatcher) {
            $this->dispatcher = new Dispatcher\GroupCountBased($this->routeCollector->getData());
        }
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
        $handler = array_key_last($routeInfo[1]);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
            case Dispatcher::METHOD_NOT_ALLOWED:
                if (array_key_exists($handler, $this->loadedMiddleware)) {
                    return $this->loadedMiddleware[$handler];
                }
                $this->loadedMiddleware[$handler] = new PipedRouteMiddleware(
                    new MiddlewarePipeline($this->loop),
                    true,
                    []
                );

                return $this->loadedMiddleware[$routeInfo[1]];
            case Dispatcher::FOUND:
                if (array_key_exists($handler, $this->loadedMiddleware)) {
                    return $this->loadedMiddleware[$handler];
                }

                $this->loadedMiddleware[$handler] = new PipedRouteMiddleware(
                    $this->getPipeline($routeInfo[1]),
                    false,
                    $routeInfo[2]
                );

                return $this->loadedMiddleware[$handler];
        }

        throw new LogicException('Something went wrong in routing.');
    }

    private function getPipeline(array $middlewarePipeline): MiddlewarePipeline
    {
        $pipeline = new MiddlewarePipeline($this->loop);
        $handler = array_pop($middlewarePipeline);

        foreach ($middlewarePipeline as $middleware) {
            $pipeline->pipe($this->middlewareFactory->create($middleware));
        }

        $requestHandlerFactory = $this->requestHandlerFactory;
        $handler = $requestHandlerFactory->create($handler);
        $callableHandler = static function (ServerRequestInterface $request) use ($handler): ResponseInterface {
            return $handler->handle($request);
        };
        $pipeline->pipe(new CallableMiddleware($callableHandler));

        return $pipeline;
    }
}
