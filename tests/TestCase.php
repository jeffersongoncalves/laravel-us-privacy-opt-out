<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Tests;

use JeffersonGoncalves\UsPrivacyOptOut\UsPrivacyOptOutServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            UsPrivacyOptOutServiceProvider::class,
        ];
    }
}
