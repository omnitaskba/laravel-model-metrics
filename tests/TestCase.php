<?php

namespace Omnitaskba\ModelMetrics\Tests;

use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase as Orchestra;
use Omnitaskba\ModelMetrics\ModelMetricsServiceProvider;
use Omnitaskba\ModelMetrics\Traits\HasMetrics;

class TestCase extends Orchestra
{
    use SetUpDatabase; // Use the new database setup trait

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ModelMetricsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('model-metrics', require __DIR__.'/../config/model-metrics.php');
    }
}