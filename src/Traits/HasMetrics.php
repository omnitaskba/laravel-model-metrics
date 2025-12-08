<?php

namespace Omnitaskba\ModelMetrics\Traits;

use Illuminate\Database\Eloquent\Builder;
use Omnitaskba\ModelMetrics\Tests\TestModel;
use Omnitaskba\ModelMetrics\Models\Metric;
use Omnitaskba\ModelMetrics\Models\AggregatedMetric;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use DateTimeInterface;
use BackedEnum;

trait HasMetrics
{
    /**
     * Increment a daily metric for the model.
     *
     * @param string|BackedEnum $name
     * @param float $value
     * @return int
     */
    public function incrementDailyMetric(string|BackedEnum $name, float $value = 1.0): int
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;
        $now = now();

        return $this->dailyMetrics()->firstOrCreate(
            [
                'name' => $metricName,
                'year' => $now->year,
                'month' => $now->month,
                'day' => $now->day,
            ],
            ['value' => 0]
        )->increment('value', $value);
    }

    /**
     * Decrement a daily metric for the model.
     *
     * @param string|BackedEnum $name
     * @param float $value
     * @return int
     */
    public function decrementDailyMetric(string|BackedEnum $name, float $value = 1.0): int
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;
        $now = now();

        return $this->dailyMetrics()->firstOrCreate(
            [
                'name' => $metricName,
                'year' => $now->year,
                'month' => $now->month,
                'day' => $now->day,
            ],
            ['value' => 0]
        )->decrement('value', $value);
    }

    /**
     * Get the metric value for the current day.
     *
     * @param string|BackedEnum $name
     * @return float
     */
    public function getTodayMetric(string|BackedEnum $name): float
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;
        $now = now();

        $metric = $this->dailyMetrics()
            ->where('name', $metricName)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->where('day', $now->day)
            ->first();

        return (float) ($metric->value ?? 0.0);
    }

    /**
     * Get the total metric value within a date range.
     *
     * @param string|BackedEnum $name
     * @param DateTimeInterface|null $startDate
     * @param DateTimeInterface|null $endDate
     * @return float
     */
    public function getTotalMetric(
        string|BackedEnum $name,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): float {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;

        $query = $this->getMetricQueryBuilder($metricName, $startDate, $endDate);

        return (float) $query->sum('value');
    }

    /**
     * Get the total metric value for the past N days, including today.
     *
     * @param string|BackedEnum $name
     * @param int $days
     * @return float
     */
    public function getTotalForPastDays(string|BackedEnum $name, int $days): float
    {
        $endDate = now();
        $startDate = now()->subDays($days - 1)->startOfDay();

        return $this->getTotalMetric($name, $startDate, $endDate);
    }

    /**
     * Get the history of a metric (grouped by day).
     *
     * @param string|BackedEnum $name
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getMetricHistory(string|BackedEnum $name, int $limit = 30): \Illuminate\Support\Collection
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;
        $dateExpression = DB::raw("year || '-' || month || '-' || day AS date_string");

        $totalValue = DB::raw('SUM(value) as total_value');

        return $this->dailyMetrics()
            ->select('name', $dateExpression, $totalValue)
            ->where('name', $metricName)
            ->groupBy('name', 'date_string')
            ->orderBy('date_string', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $item->total_value = (float) $item->total_value;
                return $item;
            });
    }

    /**
     * Define the relationship for daily metrics.
     *
     * @return MorphMany
     */
    public function dailyMetrics(): MorphMany
    {
        return $this->morphMany(
            config('model-metrics.time_series.model') ?? Metric::class,
            'model',
            null,
            null,
            'id'
        );
    }

    /**
     * Builds the base query for metric totals with date range filtering.
     *
     * @param string $metricName
     * @param DateTimeInterface|null $startDate
     * @param DateTimeInterface|null $endDate
     * @return Builder
     */
    protected function getMetricQueryBuilder(
        string $metricName,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->dailyMetrics()->getQuery()->where('name', $metricName);
        $dateExpressionString = "printf('%04d-%02d-%02d', year, month, day)";

        if ($startDate !== null) {
            $startDateString = $startDate->format('Y-m-d');
            $query->whereRaw("strftime('%Y-%m-%d', {$dateExpressionString}) >= ?", [$startDateString]);
        }

        if ($endDate) {
            $endDateString = $endDate->format('Y-m-d');
            $query->whereRaw("strftime('%Y-%m-%d', {$dateExpressionString}) <= ?", [$endDateString]);
        }

        return $query;
    }

    /**
     * Define the relationship for the single aggregated metric.
     *
     * @return MorphOne
     */
    public function aggregatedMetric(): MorphOne
    {
        return $this->morphOne(
            config('model-metrics.aggregated.model') ?? AggregatedMetric::class,
            'model'
        );
    }

    /**
     * Get an aggregated metric value.
     *
     * @param string|BackedEnum $name
     * @return float
     */
    public function getAggregatedMetric(string|BackedEnum $name): float
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;

        $metric = $this->aggregatedMetrics()->where('name', $metricName)->first();

        return (float) ($metric->value ?? 0.0);
    }

    /**
     * Check if an aggregated metric exists.
     *
     * @param string|BackedEnum $name
     * @return bool
     */
    public function hasAggregatedMetric(string|BackedEnum $name): bool
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;

        return $this->aggregatedMetrics()->where('name', $metricName)->exists();
    }

    /**
     * Set an aggregated metric value.
     *
     * @param string|BackedEnum $name
     * @param float $value
     * @return AggregatedMetric
     */
    public function setAggregatedMetric(string|BackedEnum $name, float $value): AggregatedMetric
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;

        return $this->aggregatedMetrics()->updateOrCreate(
            ['name' => $metricName],
            ['value' => $value]
        );
    }

    /**
     * Increment an aggregated metric value.
     *
     * @param string|BackedEnum $name
     * @param float $value
     * @return AggregatedMetric
     */
    public function incrementAggregatedMetric(string|BackedEnum $name, float $value = 1.0): AggregatedMetric
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;

        return $this->aggregatedMetrics()->updateOrCreate(
            ['name' => $metricName],
            ['value' => DB::raw("value + {$value}")]
        );
    }

    /**
     * Decrement an aggregated metric value.
     *
     * @param string|BackedEnum $name
     * @param float $value
     * @return AggregatedMetric
     */
    public function decrementAggregatedMetric(string|BackedEnum $name, float $value = 1.0): AggregatedMetric
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;

        return $this->aggregatedMetrics()->updateOrCreate(
            ['name' => $metricName],
            ['value' => DB::raw("value - {$value}")]
        );
    }

    /**
     * Reset an aggregated metric value to 0.
     *
     * @param string|BackedEnum $name
     * @return AggregatedMetric|null
     */
    public function resetAggregatedMetric(string|BackedEnum $name): ?AggregatedMetric
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;

        $metric = $this->aggregatedMetrics()->where('name', $metricName)->first();

        if ($metric) {
            $metric->update(['value' => 0.0]);
        }

        return $metric;
    }

    /**
     * Delete an aggregated metric record entirely.
     *
     * @param string|BackedEnum $name
     * @return bool|null
     */
    public function clearAggregatedMetric(string|BackedEnum $name): ?bool
    {
        $metricName = $name instanceof BackedEnum ? $name->value : $name;

        return $this->aggregatedMetrics()->where('name', $metricName)->delete();
    }

    /**
     * Define the relationship for aggregated metrics.
     *
     * @return MorphMany
     */
    public function aggregatedMetrics(): MorphMany
    {
        return $this->morphMany(
            config('model-metrics.aggregated.model') ?? AggregatedMetric::class,
            'model',
            null,
            null,
            'id'
        );
    }
}