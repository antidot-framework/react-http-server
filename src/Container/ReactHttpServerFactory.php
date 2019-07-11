<?php

declare(strict_types=1);

namespace Antidot\React\Container;

use Antidot\React\ReactHttpServer;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class ReactHttpServerFactory
{
    private const REQUIRED_FILE_PATH_CONFIGS = [
        'container_path',
        'router_path',
        'middleware_path',
    ];

    public function __invoke(ContainerInterface $container): ReactHttpServer
    {
        $config = $container->get('config')['react_http_server'];

        Assert::keyExists($config, 'uri');
        foreach (self::REQUIRED_FILE_PATH_CONFIGS as $filePathConfig) {
            Assert::keyExists($config, $filePathConfig);
            Assert::fileExists($config[$filePathConfig]);
        }

        return new ReactHttpServer($config);
    }
}
