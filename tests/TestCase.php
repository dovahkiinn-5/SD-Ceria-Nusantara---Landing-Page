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
        config(['view.compiled' => $compiled]);
    }
}
