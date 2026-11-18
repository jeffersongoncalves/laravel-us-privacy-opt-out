<?php

namespace JeffersonGoncalves\UsPrivacyOptOut;

use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight;
use JeffersonGoncalves\UsPrivacyOptOut\Http\HonorGlobalPrivacyControl;
use JeffersonGoncalves\UsPrivacyOptOut\Http\OptOutController;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UsPrivacyOptOutServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('us-privacy-opt-out')
            ->hasConfigFile()
            ->hasMigrations(['create_us_privacy_opt_outs_table']);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(OptOutManager::class);
    }

    public function packageBooted(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('us-privacy.gpc', HonorGlobalPrivacyControl::class);

        // @usPrivacyAllows('targeted_advertising') ... @endusPrivacyAllows: ad pixels, data broker tags.
        Blade::if('usPrivacyAllows', fn (string $right): bool => $this->app->make(OptOutManager::class)
            ->allowsForRequest(request(), OptOutRight::from($right)));

        if (! config('us-privacy-opt-out.route.enabled', false)) {
            return;
        }

        if (config('us-privacy-opt-out.route.claim_on_login', true)) {
            Event::listen(Login::class, function (Login $event): void {
                $id = request()->cookie((string) config('us-privacy-opt-out.route.cookie', 'us_privacy_opt_out_id'));

                if (is_string($id) && $id !== '' && $event->user instanceof Model) {
                    $this->app->make(OptOutManager::class)->claim($id, $event->user);
                }
            });
        }

        Route::middleware((array) config('us-privacy-opt-out.route.middleware', ['web']))
            ->post((string) config('us-privacy-opt-out.route.path', 'us-privacy/opt-out'), OptOutController::class)
            ->name('us-privacy-opt-out.store');
    }
}
