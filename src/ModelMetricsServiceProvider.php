<?php

namespace Omnitaskba\ModelMetrics;

use Illuminate\Support\ServiceProvider;

class ModelMetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/model-metrics.php', 'model-metrics'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../config/model-metrics.php' => config_path('model-metrics.php'),
            ], 'model-metrics-config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_model_metrics_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_model_metrics_table.php'),
            ], 'model-metrics-migrations');

            $this->publishes([
                __DIR__.'/../database/migrations/create_model_aggregated_metrics_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_model_aggregated_metrics_table.php'),
            ], 'model-metrics-migrations');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}