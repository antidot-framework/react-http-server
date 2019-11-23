<?php

declare(strict_types=1);

namespace Antidot\React\Runner;

use Antidot\Application\Http\Middleware\Pipeline;
use Antidot\React\CallablePipeline;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\Server;
use React\Socket\ServerInterface;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\RequestHandlerRunner;

class ReactRequestHandlerRunner extends RequestHandlerRunner
{
    /**
     * A request handler to run as the application.
     *
     * @var CallablePipeline
     */
    private $handler;

    /**
     * A factory capable of generating an error response in the scenario that
     * the $serverRequestFactory raises an exception during generation of the
     * request instance.
     *
     * The factory will receive the Throwable or Exception that caused the error,
     * and must return a Psr\Http\Message\ResponseInterface instance.
     *
     * @var callable
     */
    private $serverRequestErrorResponseGenerator;

    /**
     * A factory capable of generating a Psr\Http\Message\ServerRequestInterface instance.
     * The factory will not receive any arguments.
     *
     * @var callable
     */
    private $serverRequestFactory;

    /**
     * React Http Server
     *
     * @var ServerInterface
     */
    private $socketServer;

    /**
     * @var LoopInterface
     */
    private $loop;
    /** @var callable * */
    private $errorResponseGenerator;

    public function __construct(
        CallablePipeline $handler,
        EmitterStack $emitterStack,
        callable $errorResponseGenerator,
        ServerInterface $socketServer,
        LoopInterface $loop
    ) {
        parent::__construct(
            $handler,
            $emitterStack,
            function () {
            },
            $errorResponseGenerator
        );

        $this->handler = $handler;
        $this->loop = $loop;
        $this->socketServer = $socketServer;
        $this->errorResponseGenerator = $errorResponseGenerator;
    }

    public function run(): void
    {
        $server = new Server(function (ServerRequestInterface $request) {
            $next = clone $this->handler;
            return ($this->handler->__invoke($request, $next))->then(function (ResponseInterface $response) use (
                $request
            ) {
                $this->printResponse($request, $response);
                return $response;
            }, function (\Throwable $e) use ($request) {
                $errorResponseGenerator = $this->errorResponseGenerator;
                $this->printErrorResponse($request);

                return $errorResponseGenerator(null === $e->getPrevious() ? $e : $e->getPrevious());
            });
        });
        $server->on('error', function (\Throwable $e) {
            $this->printServerError($e);
        });
        $server->listen($this->socketServer);

        $this->loop->run();
    }

    private function printResponse(ServerRequestInterface $request, ResponseInterface $response): void
    {
        if (0 === strpos((string)$response->getStatusCode(), '20')) {
            echo sprintf(
                "[%s] \033[0;32m%s\033[0m - %s",
                $request->getMethod(),
                $response->getStatusCode(),
                $request->getUri()->getPath()
            ) . PHP_EOL;
        } else {
            echo sprintf(
                "[%s] \033[0;33m%s\033[0m - %s",
                $request->getMethod(),
                $response->getStatusCode(),
                $request->getUri()->getPath()
            ) . PHP_EOL;
        }
    }

    private function printErrorResponse(ServerRequestInterface $request): void
    {
        echo sprintf(
            "[%s] \033[0;31m500\033[0m - %s",
            $request->getMethod(),
            $request->getUri()->getPath()
        ) . PHP_EOL;
    }

    private function printServerError(\Throwable $e): void
    {
        $e = null === $e->getPrevious() ? $e : $e->getPrevious();

        echo sprintf(
            '[%s]: Server error occurred: %s, in file %s in line %s',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ) . PHP_EOL;
        echo sprintf(
            '[%s]: %s',
            get_class($e),
            $e->getTraceAsString()
        ) . PHP_EOL;
    }
}
