<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Avoid competing with the running site's Blade cache on Windows.
        $compiled = storage_path('framework/testing/views');
        if (! is_dir($compiled)) mkdir($compiled, 0775, true);
        config(['view.compiled' => $compiled, 'cache.stores.file' => ['driver' => 'array'], 'session.files' => storage_path('framework/testing/sessions/'.uniqid('test-', true))]);
        \Illuminate\Support\Facades\Cache::purge('file');
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    }
}
