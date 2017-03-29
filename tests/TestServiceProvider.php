<?php

namespace Tests;

use Illuminate\Support\ServiceProvider;

class TestMigrationsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom([
            realpath(__DIR__ . '/../migrations'),
            realpath(__DIR__ . '/database/migrations')
        ]);
    }
}
