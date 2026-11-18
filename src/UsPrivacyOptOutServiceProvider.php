<?php

namespace JeffersonGoncalves\UsPrivacyOptOut;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UsPrivacyOptOutServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-us-privacy-opt-out')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }
}
