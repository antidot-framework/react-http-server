<?php

declare(strict_types=1);

namespace Antidot\React;

use React\Http\Response;
use React\Promise\PromiseInterface;

class PromiseResponse extends Response implements PromiseInterface
{
    private PromiseInterface $promise;

    public function __construct(
        PromiseInterface $promise,
        $body = 'php://tmp',
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct($status, $headers, $body);
        $this->promise = $promise;
    }

    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null,
        callable $onProgress = null
    ): PromiseInterface {
        return $this->promise->then($onFulfilled, $onRejected, $onProgress);
    }

    public function promise(): PromiseInterface
    {
        return $this->promise;
    }
}
