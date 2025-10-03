<?php

namespace DFiks\UnPerm\Tests;

use DFiks\UnPerm\UnPermServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->artisan('migrate', ['--database' => 'testing'])->run();
    }

    protected function getPackageProviders($app): array
    {
        return [
            UnPermServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
