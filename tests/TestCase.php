<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // pages render without a front-end build, so tests never depend on npm having run
        $this->withoutVite();
        // deferred work (search engine pings, cloudinary clean-up, alert emails) runs straight away
        $this->withoutDefer();
    }
}
