<?php

namespace Tests;

error_reporting(E_ALL & ~E_DEPRECATED);

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function withoutDeprecationHandling(): self
    {
        parent::withoutDeprecationHandling();

        // Re-apply vendor filter after Laravel's strict mode overrides it
        set_error_handler(function ($errno, $errstr, $errfile) {
            if (($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) && str_contains($errfile, '/vendor/')) {
                return true;
            }
            return false;
        });

        return $this;
    }
}
