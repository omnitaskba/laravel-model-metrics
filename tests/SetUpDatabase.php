<?php

namespace Omnitaskba\ModelMetrics\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait SetUpDatabase
{
    /**
     * Set up the database environment for the tests.
     */
    protected function setUpDatabase(): void
    {
        $this->setUpSchema();
    }

    /**
     * Run the package migrations and create the dummy test model table.
     */
    protected function setUpSchema(): void
    {
        $schema = $this->app['db']->connection()->getSchemaBuilder();

        $timeSeriesMigration = include __DIR__.'/../database/migrations/create_model_metrics_table.php.stub';
        if (! $schema->hasTable('model_metrics')) {
            $timeSeriesMigration->up();
        }

        $aggregatedMigration = include __DIR__.'/../database/migrations/create_model_aggregated_metrics_table.php.stub';
        if (! $schema->hasTable('model_aggregated_metrics')) {
            $aggregatedMigration->up();
        }

        if (! $schema->hasTable('test_models')) {
            $schema->create('test_models', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }
    }
}