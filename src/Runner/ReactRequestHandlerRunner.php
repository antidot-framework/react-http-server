<?php

declare(strict_types=1);

namespace Antidot\React\Runner;

use Antidot\React\CallablePipeline;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\Server;
use React\Socket\ServerInterface;
use Throwable;
use Zend\HttpHandlerRunner\Emitter\EmitterStack;
use Zend\HttpHandlerRunner\RequestHandlerRunner;

use function React\Promise\resolve;
use function sprintf;
use function strpos;

class ReactRequestHandlerRunner extends RequestHandlerRunner
{
    /**
     * A request handler to run as the application.
     */
    private CallablePipeline $handler;

    /**
     * React Http Server
     */
    private ServerInterface $socketServer;

    private LoopInterface $loop;
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
        $handler = $this->handler;
        $errorResponseGenerator = $this->errorResponseGenerator;
        $server = new Server(static function (ServerRequestInterface $request) use ($handler, $errorResponseGenerator) {
            $next = clone $handler;
            return resolve($handler->__invoke($request, $next)->then(static function (ResponseInterface $response) use (
                $request
            ) {
                self::printResponse($request, $response);
                return $response;
            }, static function (Throwable $e) use ($request, $errorResponseGenerator) {
                self::printErrorResponse($request);

                return $errorResponseGenerator($e->getPrevious() ?? $e);
            }));
        });
        $server->on('error', static function (Throwable $e) {
            self::printServerError($e);
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

    private function printServerError(Throwable $e): void
    {
        $e = $e->getPrevious() ?? $e;

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
