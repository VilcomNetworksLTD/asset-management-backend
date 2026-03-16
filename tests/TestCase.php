<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // prevent real emails during automated tests
        \Illuminate\Support\Facades\Mail::fake();
    }
}
