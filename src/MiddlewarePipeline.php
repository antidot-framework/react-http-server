<?php

declare(strict_types=1);

namespace Antidot\React;

use Antidot\Application\Http\Handler\NextHandler;
use Antidot\Application\Http\Middleware\Pipeline;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplQueue;

final class MiddlewarePipeline implements Pipeline
{
    private $middlewareCollection;
    private $middlewareCollectionCopy;

    public function __construct(SplQueue $middlewareCollection)
    {
        $this->middlewareCollection = $middlewareCollection;
        $this->middlewareCollectionCopy = $middlewareCollection;
    }

    public function pipe(MiddlewareInterface $middleware): void
    {
        $this->middlewareCollection->enqueue($middleware);
        $this->middlewareCollectionCopy->enqueue($middleware);
    }

    public function reset(): void
    {
        $this->middlewareCollection = clone $this->middlewareCollectionCopy;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var MiddlewareInterface $middleware */
        $middleware = $this->middlewareCollection->dequeue();
        $next = clone $this;

        return $middleware->process($request, $next);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $next = new NextHandler($this->middlewareCollection, $handler);

        return $next->handle($request);
    }
}
