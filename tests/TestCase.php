<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JeffersonGoncalves\PestPluginDatabaseMigrations\LoadsPackageMigrations;
use JeffersonGoncalves\UsPrivacy\UsPrivacyServiceProvider;
use JeffersonGoncalves\UsPrivacyOptOut\UsPrivacyOptOutServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use LoadsPackageMigrations;

    protected function getPackageProviders($app): array
    {
        return [
            UsPrivacyServiceProvider::class,
            UsPrivacyOptOutServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('us-privacy-opt-out.route.enabled', true);
        $app['config']->set('us-privacy-opt-out.route.middleware', ['web']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations(UsPrivacyOptOutServiceProvider::class);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }
}
