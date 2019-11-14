<?php

declare(strict_types=1);

namespace Antidot\React;

use Antidot\Application\Http\RouteFactory;
use Antidot\Application\Http\Router;
use Antidot\Application\Http\WebServerApplication;
use Antidot\Container\MiddlewareFactory;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use Recoil\React\ReactKernel;
use Throwable;
use Zend\HttpHandlerRunner\RequestHandlerRunner;
use function React\Promise\resolve;

class ReactPHPApplication extends WebServerApplication
{
    private $kernel;

    public function __construct(
        RequestHandlerRunner $runner,
        Resettable $pipeline,
        Router $router,
        MiddlewareFactory $middlewareFactory,
        RouteFactory $routeFactory,
        LoopInterface $loop
    ) {
        parent::__construct($runner, $pipeline, $router, $middlewareFactory, $routeFactory);
        $this->kernel = ReactKernel::create($loop);
    }

    public function __invoke(ServerRequestInterface $request): PromiseInterface
    {
        return new Promise(function ($resolve, $reject) use ($request) {
            $this->kernel->execute(function () use ($resolve, $reject, $request) {
                try {
                    $response = $this->pipeline->handle($request);
                    $this->pipeline->reset();
                    $response = resolve($response);
                    $response = (yield $response);
                    $resolve($response);
                } catch (Throwable $throwable) {
                    $this->pipeline->reset();
                    $reject($throwable);
                }
            });
        });
    }
}
