<?php

declare(strict_types=1);

namespace Antidot\React\Container;

use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Psr\Container\ContainerInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;

class EmitterFactory
{
    public function __invoke(ContainerInterface $container, int $defaultBuffer = 16384): EmitterInterface
    {
        return new SapiStreamEmitter($defaultBuffer);
    }
}
