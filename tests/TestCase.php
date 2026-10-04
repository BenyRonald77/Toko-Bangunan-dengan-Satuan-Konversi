<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Praktik standar Laravel: test tidak butuh manifest Vite,
        // karena public/build tidak di-build di CI.
        $this->withoutVite();
    }
}
