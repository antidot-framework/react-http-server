# Antidot React PHP HTTP Server

> [DEPRECATED] This package will not be mantained any more. if you want to goin react with Antidot framework, take a look at [Reactive Starter package](https://github.com/antidot-framework/reactive-antidot-starter)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/antidot-framework/react-http-server/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/antidot-framework/react-http-server/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/antidot-framework/react-http-server/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/antidot-framework/react-http-server/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/antidot-framework/react-http-server/badges/build.png?b=master)](https://scrutinizer-ci.com/g/antidot-framework/react-http-server/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/antidot-framework/react-http-server/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Adapter that allows running [Antidot Framework Applications](https://github.com/antidot-framework/antidot-starter) on a 
[React PHP server](https://github.com/reactphp/http)

## Installation:

Using [composer package manager](https://getcomposer.org/download/)

````bash
composer require antidot-fw/antidot-react-http:dev-master
````

We have to add the config provider to the framework configuration to load the necessary dependencies, we make sure to 
load it after the provider of the framework itself.

````php
<?php
// config/config.php
declare(strict_types=1);

$aggregator = new ConfigAggregator([
    // ... other config providers
    \Antidot\Container\Config\ConfigProvider::class, // Framework default config provider
    \Antidot\React\Container\Config\ConfigProvider::class, // React Application config provider
    new PhpFileProvider(realpath(__DIR__).'/services/{{,*.}prod,{,*.}local,{,*.}dev}.php'),
    new YamlConfigProvider(realpath(__DIR__).'/services/{{,*.}prod,{,*.}local,{,*.}dev}.yaml'),
    new ArrayProvider($cacheConfig),
], $cacheConfig['config_cache_path']);

return $aggregator->getMergedConfig();
````

## Running server:

We just need to run a console command and we'll have the server up and running on port `8080`.

````bash
bin/console react-server:http
````

## Server config:

````yaml
parameters:
  react_http_server:
    uri: "localhost:8080"  
````
