<?php

namespace Omnitaskba\ModelMetrics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder<\Omnitaskba\ModelMetrics\Models\AggregatedMetric>
 * @property float $value
 */
class AggregatedMetric extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('model-metrics.aggregated.table_name', 'model_aggregated_metrics');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}