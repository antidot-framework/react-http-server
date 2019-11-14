<?php

declare(strict_types=1);

namespace Antidot\React;

use Antidot\Application\Http\Middleware\Pipeline;

interface Resettable extends Pipeline
{
    public function reset(): void;
}
