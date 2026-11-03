<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // DatabaseMailable counts mail failures for the whole process; start each test from none
        \App\Mail\DatabaseMailable::forgetFailures();
    }
}
