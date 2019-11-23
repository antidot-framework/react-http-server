<?php

declare(strict_types=1);

namespace Antidot\React\Container;

use Antidot\Application\Http\Response\ErrorResponseGenerator;
use Antidot\Application\Http\RouteFactory;
use Antidot\Application\Http\Router;
use Antidot\Application\Http\WebServerApplication;
use Antidot\Application\Http\Application;
use Antidot\Container\MiddlewareFactory;
use Antidot\React\CallablePipeline;
use Antidot\React\MiddlewarePipeline;
use Antidot\React\Runner\ReactRequestHandlerRunner;
use Psr\Container\ContainerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\RequestHandlerRunner;

class ApplicationFactory
{
    public function __invoke(ContainerInterface $container): Application
    {
        $pipeline = new MiddlewarePipeline(
            $container->get(LoopInterface::class)
        );
        return new WebServerApplication(
            $this->getRunner($container, $pipeline),
            $pipeline,
            $container->get(Router::class),
            $container->get(MiddlewareFactory::class),
            $container->get(RouteFactory::class)
        );
    }

    private function getRunner(ContainerInterface $container, CallablePipeline $pipeline): RequestHandlerRunner
    {
        return new ReactRequestHandlerRunner(
            $pipeline,
            $container->get(EmitterStack::class),
            $container->get(ErrorResponseGenerator::class),
            $container->get(ServerInterface::class),
            $container->get(LoopInterface::class)
        );
    }
}
