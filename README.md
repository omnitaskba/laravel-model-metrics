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

The package creates two dedicated tables: `metrics` and `aggregated_metrics`.
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
}
```

### 1. Daily Metrics (Time Series)

Daily metrics are stored per day (year, month, day) and are best for tracking historical data.

**Core Methods**

| Method                                   | Description                              | Example Use                                                   |
|------------------------------------------|-------------------------------------------|---------------------------------------------------------------|
| `incrementDailyMetric(name, value = 1)`  | Increments the metric value for today.    | `$user->incrementDailyMetric(UserMetric::VIEWS, 5);`         |
| `decrementDailyMetric(name, value = 1)`  | Decrements the metric value for today.    | `$user->decrementDailyMetric('refunds', 1.5);`               |
| `getTodayMetric(name)`                   | Retrieves the current value for today.    | `$user->getTodayMetric(UserMetric::VIEWS);`                  |

**History & Range Queries**

| Method                | Description                                                   | Arguments                    | Returns     |
|-----------------------|---------------------------------------------------------------|-------------------------------|-------------|
| `getTotalMetric()`    | SUM of the metric within the given date range.                | `?startDate, ?endDate`        | `float`     |
| `getTotalForPastDays()` | SUM for the last N days (including today).                  | `int $days`                   | `float`     |
| `getMetricHistory()`  | Collection of daily records, sorted descending by date.       | `int $limit = 30`             | `Collection` |

Example of Range Query:
```php
use DateTime;
$start = new DateTime('2025-10-01');
$end = new DateTime('2025-10-31');

// Get total sales for the month of October
$totalSales = $user->getTotalMetric(UserMetric::SALES, $start, $end); // float(1250.0)
```

### 2. Aggregated Metrics (Single Value)

Aggregated metrics store a single value (e.g., a total score or streak count) and are persisted until explicitly changed or cleared.

| Method                                       | Description                                        | Example Use                                                     |
|----------------------------------------------|----------------------------------------------------|-----------------------------------------------------------------|
| `setAggregatedMetric(name, value)`           | Sets or overwrites the metric value.               | `$user->setAggregatedMetric('high_score', 999);`               |
| `incrementAggregatedMetric(name, value = 1)` | Increases the value.                               | `$user->incrementAggregatedMetric(UserMetric::STREAK);`        |
| `decrementAggregatedMetric(name, value = 1)` | Decreases the value.                               | `$user->decrementAggregatedMetric('hp', 5);`                   |
| `getAggregatedMetric(name)`                  | Retrieves the value (defaults to 0.0 if not set).  | `$user->getAggregatedMetric(UserMetric::STREAK);`              |
| `resetAggregatedMetric(name)`                | Resets the metric value to zero (0.0).             | `$user->resetAggregatedMetric('streak');`                      |
| `clearAggregatedMetric(name)`                | Deletes the metric record (row is removed).        | `$user->clearAggregatedMetric('old_metric');`                  |

## ⚙️ Configuration

You can publish the configuration file to customize the table names used by the package:

```bash
php artisan vendor:publish --tag=laravel-model-metrics-config
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


