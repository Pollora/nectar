<?php

declare(strict_types=1);

namespace Pollora\Nectar\Tests;

use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Pollora\Nectar\NectarServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            NectarServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('nectar.enabled', true);
        $app['env'] = 'local';
    }
}
