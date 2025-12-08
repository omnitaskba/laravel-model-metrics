<?php

return [
    'time_series' => [
        'table_name' => 'model_metrics',
        'model' => \Omnitaskba\ModelMetrics\Models\Metric::class,
    ],

    'aggregated' => [
        'table_name' => 'model_aggregated_metrics',
        'model' => \Omnitaskba\ModelMetrics\Models\AggregatedMetric::class,
    ],
];