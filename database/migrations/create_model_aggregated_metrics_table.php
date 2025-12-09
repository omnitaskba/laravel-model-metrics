<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = config('model-metrics.aggregated.table_name') ?? 'model_aggregated_metrics';

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('name');
            $table->decimal('value', 20, 2)->default(0);
            $table->timestamps();
            $table->unique(['model_type', 'model_id', 'name'], 'aggregated_metric_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('model-metrics.aggregated.table_name', 'model_aggregated_metrics');
        Schema::dropIfExists($tableName);
    }
};