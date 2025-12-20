<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        try {
            if (function_exists('tenancy')) {
                tenancy()->end();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $defaultConnection = (string) env('DB_CONNECTION', 'sqlite_landlord');
        config(['database.default' => $defaultConnection]);
        DB::setDefaultConnection($defaultConnection);
        
        // Disable VACUUM for SQLite in-memory databases during testing
        // VACUUM cannot run inside transactions and causes errors in CI
        if (app()->environment('testing')) {
            try {
                // Disable auto_vacuum for landlord connection
                if (config('database.connections.sqlite_landlord')) {
                    DB::connection('sqlite_landlord')->statement('PRAGMA auto_vacuum = NONE');
                }
                
                // Disable auto_vacuum for tenant connection
                if (config('database.connections.sqlite_tenant')) {
                    DB::connection('sqlite_tenant')->statement('PRAGMA auto_vacuum = NONE');
                }
            } catch (\Exception $e) {
                // Silently ignore if connections don't exist yet
            }
        }
    }

    protected function tearDown(): void
    {
        try {
            if (function_exists('tenancy')) {
                tenancy()->end();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        parent::tearDown();
    }
}
