<?php

declare(strict_types=1);

namespace Antidot\React\Container;

use Antidot\Application\Http\WebServerApplication;
use Antidot\React\MiddlewarePipeline;
use Antidot\Application\Http\Middleware\Pipeline;
use Antidot\Application\Http\Response\ErrorResponseGenerator;
use Antidot\Application\Http\RouteFactory;
use Antidot\Application\Http\Router;
use Antidot\Container\MiddlewareFactory;
use Antidot\Container\RequestFactory;
use Antidot\React\ReactPHPApplication;
use Psr\Container\ContainerInterface;
use React\EventLoop\LoopInterface;
use SplQueue;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\RequestHandlerRunner;

final class ApplicationFactory
{
    public function __invoke(ContainerInterface $container): WebServerApplication
    {
        $pipeline = new MiddlewarePipeline(new SplQueue());
        $runner = $this->getRunner($container, $pipeline);
        return new ReactPHPApplication(
            $runner,
            $pipeline,
            $container->get(Router::class),
            $container->get(MiddlewareFactory::class),
            $container->get(RouteFactory::class),
            $container->get(LoopInterface::class)
        );
    }

    private function getRunner(ContainerInterface $container, Pipeline $pipeline): RequestHandlerRunner
    {
        return new RequestHandlerRunner(
            $pipeline,
            $container->get(EmitterStack::class),
            ($container->get(RequestFactory::class))(),
            $container->get(ErrorResponseGenerator::class)
        );
    }
}
