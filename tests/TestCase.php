<?php

declare(strict_types=1);

namespace Suenerds\LaravelDatastar\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Suenerds\LaravelDatastar\LaravelDatastarServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDatastarServiceProvider::class,
        ];
    }
}
