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
    private array $middleware;
    private ReactKernel $kernel;

    public function __construct(LoopInterface $loop)
    {
        $this->kernel = ReactKernel::create($loop);
        $this->middleware = [];
    }

    public function pipe(MiddlewareInterface $middleware): void
    {
        $func = static function ($middleware) {
            return $middleware;
        };

        $this->middleware[] = $func($middleware);
    }

    public function __invoke(ServerRequestInterface $request, callable $next): PromiseInterface
    {
        $stack = $this->createStack($next);
        $kernel = $this->kernel;

        return new Promise(static function ($resolve, $reject) use ($kernel, $request, $next, $stack) {
            $kernel->execute(static function () use ($resolve, $reject, $request, $next, $stack) {
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

    private function createStack($next): callable
    {
        $stack = static function (ServerRequestInterface $request) use ($next) {
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
            $stack = static function (ServerRequestInterface $request) use ($stack, $mwh) {
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
        return new PromiseResponse(resolve(clone $this)->then(fn($next) => $this->__invoke($request, $next)));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new PromiseResponse(resolve(static function ($handler) {
            return $handler;
        })->then(fn($func) => $this->__invoke($request, $func($handler))));
    }
}
