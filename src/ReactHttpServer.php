<?php

declare(strict_types=1);

namespace Antidot\React;

use Antidot\Application\Http\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReactHttpServer extends Command
{
    public const NAME = 'react-server:http';
    private $config;

    public function __construct(array $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    protected function configure(): void
    {
        $this->setName(self::NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $middleware = require $this->config['middleware_path'];
        $routes = require $this->config['router_path'];
        $container = require $this->config['container_path'];
        /** @var Application $application */
        $application = $container->get(Application::class);
        $middleware($application, $container);
        $routes($application, $container);

        $application->run();
    }
}
