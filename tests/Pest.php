<?php

use Omnitaskba\ModelMetrics\Tests\TestCase;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The TestCase is the foundation for writing tests. It provides the setup
| and teardown necessary for running your tests in a clean environment.
|
*/

uses(TestCase::class)->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Global Helpers
|--------------------------------------------------------------------------
|
| Define global helpers necessary for the test suite.
|
*/
if (! function_exists('setTestNow')) {
    /**
     * Set a fixed point in time for testing.
     * * @param \DateTimeInterface|null $time
     * @return void
     */
    function setTestNow(?DateTimeInterface $time): void
    {
        // This is the implementation that actually freezes time using Carbon,
        // which is available via Orchestra Testbench.
        Carbon::setTestNow($time);
    }
}