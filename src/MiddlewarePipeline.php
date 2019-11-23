<?php

declare(strict_types=1);

namespace Antidot\React;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use Recoil\React\ReactKernel;
use Throwable;
use function React\Promise\resolve;

final class MiddlewarePipeline implements CallablePipeline
{
    /** @var array * */
    private $middleware;
    private $kernel;

    public function __construct(LoopInterface $loop)
    {
        $this->kernel = ReactKernel::create($loop);
        $this->middleware = [];
    }

    public function pipe(MiddlewareInterface $middleware): void
    {
        $func = function ($middleware) {
            return $middleware;
        };

        $this->middleware[] = $func($middleware);
    }

    public function __invoke(ServerRequestInterface $request, callable $next): PromiseInterface
    {
        $stack = $this->createStack($next);

        return new Promise(function ($resolve, $reject) use ($request, $next, $stack) {
            $this->kernel->execute(function () use ($resolve, $reject, $request, $next, $stack) {
                try {
                    $response = $stack($request, $next);
                    if ($response instanceof ResponseInterface) {
                        $response = resolve($response);
                    }
                    $response = (yield $response);
                    $resolve($response);
                } catch (Throwable $throwable) {
                    $reject($throwable);
                }
            });
        });
    }

    private function createStack($next)
    {
        $stack = function (ServerRequestInterface $request) use ($next) {
            $response = $next($request);
            if ($response instanceof ResponseInterface) {
                $response = resolve($response);
            }
            return (yield $response);
        };

        $middleware = $this->middleware;
        $middleware = array_reverse($middleware);
        foreach ($middleware as $mw) {
            $mwh = clone $mw;
            $stack = function (ServerRequestInterface $request) use ($stack, $mwh) {
                $response = $mwh->process($request, new PassThroughRequestHandler($stack));
                if ($response instanceof ResponseInterface) {
                    $response = resolve($response);
                }
                return (yield $response);
            };
        }

        return $stack;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $next = clone $this;

        return new PromiseResponse($this->__invoke($request, $next));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $func = function ($handler) {
            return $handler;
        };

        return new PromiseResponse($this->__invoke($request, $func($handler)));
    }
}
