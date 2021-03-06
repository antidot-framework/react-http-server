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

use function array_pop;
use function var_export;

class ReactFastRouter implements Router
{
    private RouteCollector $routeCollector;
    private MiddlewareFactory $middlewareFactory;
    private RequestHandlerFactory $requestHandlerFactory;
    private ?Dispatcher\GroupCountBased $dispatcher = null;
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
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
            case Dispatcher::METHOD_NOT_ALLOWED:
                return new PipedRouteMiddleware(
                    new MiddlewarePipeline($this->loop),
                    true,
                    []
                );
            case Dispatcher::FOUND:
                return new PipedRouteMiddleware(
                    $this->getPipeline($routeInfo[1]),
                    false,
                    $routeInfo[2]
                );
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
