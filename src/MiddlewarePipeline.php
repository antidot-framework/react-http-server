<?php

declare(strict_types=1);

namespace Antidot\React;

use Antidot\Application\Http\Handler\NextHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplQueue;

final class MiddlewarePipeline implements Resettable
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
        $this->middlewareCollectionCopy = clone $this->middlewareCollection;
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

        $response = $middleware->process($request, $next);
        unset($next);

        return $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $next = new NextHandler($this->middlewareCollection, $handler);

        $response = $next->handle($request);
        unset($next);

        return $response;
    }
}
