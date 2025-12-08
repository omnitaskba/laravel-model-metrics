<?php

use Omnitaskba\ModelMetrics\Models\Metric;
use Omnitaskba\ModelMetrics\Models\AggregatedMetric;
use Illuminate\Database\Eloquent\Model;
use Omnitaskba\ModelMetrics\Traits\HasMetrics;
use Illuminate\Support\Collection;

class TestModel extends Model
{
    use HasMetrics;

    protected $guarded = [];
    protected $table = 'test_models';
}

enum TestMetric: string
{
    case View = 'view_count';
    case Sales = 'sales_count';
    case Streak = 'current_streak';
}


beforeEach(function () {
    setTestNow(new DateTime('2025-10-20 10:00:00', new DateTimeZone('UTC')));
    $this->model = TestModel::create();

    $this->model->incrementDailyMetric('logins', 10); // Oct 20: 10
    $this->model->incrementDailyMetric('clicks', 1);  // Oct 20: 1
    $this->model->incrementDailyMetric(TestMetric::Sales, 10); // Oct 20: 10

    setTestNow(new DateTime('2025-10-19 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric('logins', 5); // Oct 19: 5

    setTestNow(new DateTime('2025-10-13 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric('logins', 2); // Oct 13: 2

    // Final day for most tests (Oct 22)
    setTestNow(new DateTime('2025-10-22 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric(TestMetric::Sales, 5); // Oct 22: 5
});

it('can increment and get today\'s metric', function () {
    // Use the current mocked time (Oct 22)
    $this->model->incrementDailyMetric(TestMetric::View, 5);
    $this->model->incrementDailyMetric('custom_count', 2.5);

    // so it's 3 distinct days + 1 new day (View, custom_count) = 5
    expect(Metric::count())->toEqual(8)
        ->and($this->model->getTodayMetric(TestMetric::View))->toEqual(5.0)
        ->and($this->model->getTodayMetric('custom_count'))->toEqual(2.5);
});

it('handles daily decrement and total sum', function () {
    setTestNow(new DateTime('2025-10-21 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric('hits', 5);
    $this->model->decrementDailyMetric('hits', 2);

    expect($this->model->getTodayMetric('hits'))->toEqual(3.0)
        ->and($this->model->getTotalMetric('logins'))->toEqual(17.0);
});

it('correctly filters getTotalMetric by date range', function () {
    // Range is 2025-10-20 to 2025-10-21.
    // Data in range: Oct 20 (Sales: 10), Oct 21 (None)
    // Data outside range: Oct 19 (logins: 5), Oct 22 (Sales: 5)

    // Add data for Oct 21 to test boundary inclusion
    setTestNow(new DateTime('2025-10-21 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric(TestMetric::Sales, 20); // Oct 21: 20

    $startDate = new DateTime('2025-10-20');
    $endDate = new DateTime('2025-10-21');

    // Expected Total: Oct 20 (10) + Oct 21 (20) = 30.0
    $total = $this->model->getTotalMetric(TestMetric::Sales, $startDate, $endDate);
    expect($total)->toEqual(30.0);
});

it('calculates totals for past N days correctly', function () {
    // Current mocked time is Oct 22.
    // Logins data: Oct 20 (10), Oct 19 (5), Oct 13 (2)

    // Total for past 2 days (Oct 21, Oct 22): Should be 0, as only Oct 20 has data.
    // Let's increment Oct 21 and Oct 22 data for proper test:
    setTestNow(new DateTime('2025-10-21 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric('logins', 2); // Oct 21: 2

    setTestNow(new DateTime('2025-10-22 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric('logins', 3); // Oct 22: 3

    // Past 2 days (Oct 21 and Oct 22): Expected 2 + 3 = 5.0
    expect($this->model->getTotalForPastDays('logins', 2))->toEqual(5.0);

    // Past 4 days (Oct 19, Oct 20, Oct 21, Oct 22):
    // Oct 19 (5) + Oct 20 (10) + Oct 21 (2) + Oct 22 (3) = 20.0
    expect($this->model->getTotalForPastDays('logins', 4))->toEqual(20.0);
});

it('can get history for a specific metric', function () {
    // Data is: Oct 20 (1)
    setTestNow(new DateTime('2025-10-21 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric('clicks', 5);
    $this->model->incrementDailyMetric('clicks', 5); // Oct 21: 10

    $history = $this->model->getMetricHistory('clicks');

    // History is ordered DESC by date: [0] = Oct 21 (10.0), [1] = Oct 20 (1.0)
    expect($history)->toBeInstanceOf(Collection::class)
        ->and($history)->toHaveCount(2)
        ->and($history[0]->total_value)->toEqual(10.00)
        ->and($history[1]->total_value)->toEqual(1.00);
});

it('handles decimal increments and decrements precisely', function () {
    $this->model->incrementDailyMetric('price', 1.23);
    $this->model->incrementDailyMetric('price', 4.56);
    $this->model->decrementDailyMetric('price', 0.79);

    // Expected: 1.23 + 4.56 - 0.79 = 5.0
    $actualValue = $this->model->getTodayMetric('price');
    expect(round($actualValue, 8))->toEqual(5.0);
});

it('correctly filters totals across month and year boundaries', function () {
    // Test data from setup is cleared/not relevant here, we create new data
    $this->model->dailyMetrics()->delete();

    // Dec 31
    setTestNow(new DateTime('2025-12-31 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric('events', 5); // Dec 31: 5

    // Jan 01
    setTestNow(new DateTime('2026-01-01 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric('events', 10); // Jan 01: 10

    // Jan 02
    setTestNow(new DateTime('2026-01-02 10:00:00', new DateTimeZone('UTC')));
    $this->model->incrementDailyMetric('events', 2); // Jan 02: 2

    // Filter range: Dec 31, 2025 to Jan 01, 2026
    $startDate = new DateTime('2025-12-31');
    $endDate = new DateTime('2026-01-01');

    // Expected: Dec 31 (5) + Jan 01 (10) = 15.0
    $total = $this->model->getTotalMetric('events', $startDate, $endDate);
    expect($total)->toEqual(15.0);
});

it('returns 0.0 for total metric in an empty date range', function () {
    $startDate = new DateTime('2030-01-01');
    $endDate = new DateTime('2030-01-31');

    // Expected: 0.0 (no metrics exist in 2030)
    $total = $this->model->getTotalMetric('logins', $startDate, $endDate);
    expect($total)->toEqual(0.0);
});

it('can set, increment, and decrement aggregated metrics', function () {
    // Set 50, Increment 10, Decrement 2 = 58.0
    $this->model->setAggregatedMetric(TestMetric::Streak, 50);
    $this->model->incrementAggregatedMetric(TestMetric::Streak, 10);
    $this->model->decrementAggregatedMetric(TestMetric::Streak, 2);

    expect(AggregatedMetric::count())->toEqual(1)
        ->and($this->model->getAggregatedMetric(TestMetric::Streak))->toEqual(58.0);
});

it('correctly identifies if an aggregated metric exists', function () {
    //Non-Existent Get - Should be false
    expect($this->model->hasAggregatedMetric('score'))->toBeFalse();

    $this->model->setAggregatedMetric('score', 1);

    //Non-Existent Get - Ensure getAggregatedMetric returns 0.0 for non-existent metric
    expect($this->model->hasAggregatedMetric('score'))->toBeTrue()
        ->and($this->model->getAggregatedMetric('non_existent'))->toEqual(0.0);

});

it('can reset an aggregated metric to zero', function () {
    $this->model->setAggregatedMetric('reset_test', 99);

    $this->model->resetAggregatedMetric('reset_test');

    expect($this->model->getAggregatedMetric('reset_test'))->toEqual(0.0)
        ->and(AggregatedMetric::count())->toEqual(1);
});

it('can clear an aggregated metric (delete the row)', function () {
    $this->model->setAggregatedMetric('clear_test', 99);
    expect(AggregatedMetric::count())->toEqual(1)
        ->and($this->model->clearAggregatedMetric('clear_test'))->toBeTrue()
        ->and(AggregatedMetric::count())->toEqual(0);

});

it('handles clearing a non-existent aggregated metric gracefully', function () {
    //Clear Non-Existent - Should return true (or false depending on trait implementation,
    // but should not throw an error)
    $result = $this->model->clearAggregatedMetric('does_not_exist');

    // Eloquent delete on a query that finds nothing typically returns 0 (rows affected),
    // but the clear method often returns true/null. We expect no error and
    // that the database count remains 0.
    expect(AggregatedMetric::count())->toEqual(0)
        ->and($result)->toEqual(0);

});