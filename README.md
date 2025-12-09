# Laravel Model Metrics 📊

[![Build Status](https://github.com/omnitaskba/laravel-model-metrics/actions/workflows/ci.yml/badge.svg)](https://github.com/omnitaskba/laravel-model-metrics/actions/workflows/ci.yml)
[![Latest Stable Version](http://poser.pugx.org/omnitaskba/laravel-model-metrics/v)](https://packagist.org/packages/omnitaskba/laravel-model-metrics)
[![Total Downloads](http://poser.pugx.org/omnitaskba/laravel-model-metrics/downloads)](https://packagist.org/packages/omnitaskba/laravel-model-metrics)
[![License](http://poser.pugx.org/omnitaskba/laravel-model-metrics/license)](https://packagist.org/packages/omnitaskba/laravel-model-metrics)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%20%7C%208.4-blue.svg)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-11%20%7C%2012-orange.svg)](https://laravel.com/)

A simple and non-intrusive Laravel package that enables **daily time-series metrics** and **aggregated single-value metrics** for any Eloquent model --- without adding clutter to your main tables.

Perfect for tracking **views**, **logins**, **sales**, **XP**, **streaks**, and more.

---

## 🚀 Features

* 📅 **Daily Metrics:** Store values tracked per day (`year`, `month`, `day`).
* 🧮 **Aggregated Metrics:** Store a single key-value score (e.g., current streak).
* 🏷️ Supports **Strings** or **PHP Backed Enums** for metric names.
* ⚡ **Efficient:** Uses dedicated tables and optimized SUM queries.
* 🔌 **Plug-and-Play:** Activated by a single trait.
* 🛠️ **Configurable:** Customize table names easily.

---

## 📦 Installation

### 1. Install via Composer

```bash
composer require omnitaskba/laravel-model-metrics
```

### 2. Run Migrations

The package creates two dedicated tables: `model_metrics` and `model_aggregated_metrics`.
```bash
php artisan migrate
```

### 3. Add the trait to your model

Apply the `HasMetrics` trait to any Eloquent model you wish to track metrics for.
```php
// app/Models/User.php

use Omnitaskba\ModelMetrics\Traits\HasMetrics;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasMetrics;
    // ...
}
```

## 📚 Usage

Metric names can be passed as a string ('views') or, preferably, as a `Backed Enum` (PHP 8.1+):
```php
// Example Backed Enum
enum UserMetric: string {
    case VIEWS = 'views';
    case SALES = 'sales';
    case STREAK = 'streak';
    case HIGH_SCORE = 'high_score';
    // ...
}
```

## Daily & Aggregated Metrics Documentation

### 📅 Daily Metrics (Time Series)

Daily metrics are stored by day (year, month, day) and track historical,
time‑sensitive data.

------------------------------------------------------------------------

#### 1. `incrementDailyMetric()`

Increments the metric value for the current day. Creates the daily
record if it doesn't exist.

| Argument | Type                | Default  | Description                              |
|----------|---------------------|----------|------------------------------------------|
| `name`   | string or BackedEnum | Required | The name of the metric (e.g., `UserMetric::HIGH_SCORE`).  |
| `value`  | float               | 1.0      | Amount to increment by.                  |


##### Example

``` php
// Assuming current date is 2025-12-09
$user->incrementDailyMetric(UserMetric::HIGH_SCORE);       // Value: 1.0
$user->incrementDailyMetric(UserMetric::HIGH_SCORE, 5.5);  // Value: 6.5
```

**Returns:** New value of the metric record.

------------------------------------------------------------------------

#### 2. `decrementDailyMetric()`

Decrements the metric value for the current day. Creates the daily
record if it doesn't exist.

| Argument | Type                | Default  | Description               |
|----------|---------------------|----------|---------------------------|
| `name`   | string or BackedEnum | Required | The metric name.          |
| `value`  | float               | 1.0      | Amount to decrement by.   |

##### Example

``` php
// Assuming current daily high_score are 20.0
$user->decrementDailyMetric(UserMetric::HIGH_SCORE, 0.5);
```

**Returns:** New value (`19.5` in this case).

------------------------------------------------------------------------

#### 3. `getTodayMetric()`

Retrieves today's metric value.

| Argument | Type                | Default  | Description  |
|----------|---------------------|----------|--------------|
| `name`   | string or BackedEnum | Required | Metric name. |

##### Example

``` php
$todayViews = $user->getTodayMetric(UserMetric::HIGH_SCORE);
```

**Returns:**\
`float(19.5)` --- or `0.0` if none exists.

------------------------------------------------------------------------

#### 4. `getTotalMetric()`

Calculates the sum across all days between two dates (inclusive).

| Argument     | Type                | Default | Description |
|--------------|---------------------|---------|-------------|
| `name`       | string or BackedEnum | Required | Metric name. |
| `startDate`  | DateTimeInterface   | null    | Start date. |
| `endDate`    | DateTimeInterface   | null    | End date. |

##### Example

``` php
use DateTime;

$start = new DateTime('2025-10-01');
$end = new DateTime('2025-10-31');

$totalSales = $user->getTotalMetric(ProductMetric::SALES, $start, $end);
```

**Returns:** `float(1250.0)` or `0.0` if no data.

------------------------------------------------------------------------

#### 5. `getTotalForPastDays()`

Sums the metric for the last N days (including today).

| Argument | Type                | Default  | Description                     |
|----------|---------------------|----------|---------------------------------|
| `name`   | string or BackedEnum | Required | Metric name.                    |
| `days`   | int                 | Required | How many days to look back.     |


##### Example

``` php
$lastWeekViews = $user->getTotalForPastDays(ProductMetric::VIEWS, 7);
```

**Returns:** `float(85.5)`

------------------------------------------------------------------------

#### 6. `getMetricHistory()`

Retrieves a collection of daily metric records grouped by day, sorted
descending.

| Argument | Type                | Default | Description              |
|----------|---------------------|---------|--------------------------|
| `name`   | string or BackedEnum | Required | Metric name.             |
| `limit`  | int                 | 30      | Max number of records.   |

##### Example

``` php
$history = $user->getMetricHistory(ProductMetric::VIEWS, 10);
```

------------------------------------------------------------------------

### 📊 Aggregated Metrics (Single Value)

Aggregated metrics store a single persistent value.

------------------------------------------------------------------------

#### 1. `setAggregatedMetric()`

Sets or overwrites the metric value.

| Argument | Type                | Default  | Description    |
|----------|---------------------|----------|----------------|
| `name`   | string or BackedEnum | Required | Metric name.   |
| `value`  | float               | Required | Value to set.  |

##### Example

``` php
$user->setAggregatedMetric(UserMetric::HIGH_SCORE, 999.5);
```

------------------------------------------------------------------------

#### 2. `incrementAggregatedMetric()`

Increases the metric value.

| Argument | Type                | Default | Description        |
|----------|---------------------|---------|--------------------|
| `name`   | string or BackedEnum | Required | Metric name.       |
| `value`  | float               | 1.0     | Increment amount.  |

##### Example

``` php
$user->incrementAggregatedMetric(UserMetric::LOGIN_STREAK); // New value: 6.0
```

------------------------------------------------------------------------

#### 3. `decrementAggregatedMetric()`

Decreases the value.

| Argument | Type                | Default | Description        |
|----------|---------------------|---------|--------------------|
| `name`   | string or BackedEnum | Required | Metric name.       |
| `value`  | float               | 1.0     | Decrement amount.  |

##### Example

``` php
$user->decrementAggregatedMetric(UserMetric::HIGH_SCORE, 5.5); // New value: 94.5
```

------------------------------------------------------------------------

#### 4. `getAggregatedMetric()`

Retrieves current aggregated value.

##### Example

``` php
$currentScore = $user->getAggregatedMetric(UserMetric::HIGH_SCORE);
```

**Returns:** `float(999.5)` (or `0.0` if missing)

------------------------------------------------------------------------

#### 5. `hasAggregatedMetric()`

Checks if the record exists.

##### Example

``` php
if ($user->hasAggregatedMetric(UserMetric::HIGH_SCORE)) {
    // ...
}
```

------------------------------------------------------------------------

#### 6. `resetAggregatedMetric()`

Sets value to `0.0` (record stays).

##### Example

``` php
$user->resetAggregatedMetric(UserMetric::LOGIN_STREAK);
```

------------------------------------------------------------------------

#### 7. `clearAggregatedMetric()`

Deletes the aggregated metric record entirely.

##### Example

``` php
$user->clearAggregatedMetric('old_metric');
```

**Returns:** `true` on success, `false` if not found.


## ⚙️ Configuration

You can publish the configuration file to customize the table names used by the package:

```bash
php artisan vendor:publish --tag=model-metrics-config
```

This will create a config/model-metrics.php file with the following contents, allowing you to customize the table names and model classes used for each metric type:
```php
// config/model-metrics.php
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
```

**Key Configuration Options:**
- `time_series.table_name`: The table used for Daily Metrics (Time Series).
- `aggregated.table_name`: The table used for Aggregated Metrics (Single Value).

## 🔧 Performance Notes
- Indexes: The underlying tables (metrics and aggregated_metrics) are indexed on the required columns (name, model_id, model_type, and date columns) to ensure rapid retrieval and SUM operations.
- Unique Constraints: Daily metrics use a unique constraint on (name, year, month, day, model_id, model_type) to prevent duplicate entries and ensure atomic updates.

## 🧪 Testing

You can run the full test suite with:
```bash
composer test
```

## 🤝 Contributing

See **CONTRIBUTING.md**.

## 📄 Changelog

Please see **CHANGELOG.md** for more information what has changed recently.

## 🔐 Security

If you discover any security related issues, please email zlatan@omnitask.ba instead of using the issue tracker.

## 💡 Credits
- [Zlatan Goralija](https://github.com/zlatangoralija)


## 📄 License

Laravel Model Metrics is open-sourced software licensed under the MIT License.


