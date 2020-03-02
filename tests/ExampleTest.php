<?php

namespace PollieDev\LaravelErrorTracker\Tests;

use Orchestra\Testbench\TestCase;
use PollieDev\LaravelErrorTracker\LaravelErrorTrackerServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [LaravelErrorTrackerServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
