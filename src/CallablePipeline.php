<?php

declare(strict_types=1);

namespace Antidot\React;

use Antidot\Application\Http\Middleware\Pipeline;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

interface CallablePipeline extends Pipeline
{
    public function __invoke(ServerRequestInterface $request, callable $next): PromiseInterface;
}
