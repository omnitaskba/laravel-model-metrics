<?php

namespace Omnitaskba\ModelMetrics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder<\Omnitaskba\ModelMetrics\Models\Metric>
 * @property float $value
 * @property-read float $total_value // For aliased SUM/SELECT calls
 */
class Metric extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('model-metrics.table_name', 'model_metrics');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}