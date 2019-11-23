<?php

declare(strict_types=1);

namespace Antidot\React;

use Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class PassThroughRequestHandler implements RequestHandlerInterface
{
    /**
     * @var callable
     */
    private $next;

    /**
     * @param callable $next
     */
    public function __construct(callable $next)
    {
        $this->next = $next;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $next = $this->next;
        /** @var Generator $handlerGenerator */
        $handlerGenerator = $next($request);
        $next = null;

        return new PromiseResponse($handlerGenerator->current());
    }
}
