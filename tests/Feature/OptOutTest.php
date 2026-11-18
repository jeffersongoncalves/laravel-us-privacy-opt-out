<?php

use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\UsPrivacy\Facades\UsPrivacy;
use JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutAction;
use JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight;
use JeffersonGoncalves\UsPrivacyOptOut\Events\OptOutRecorded;
use JeffersonGoncalves\UsPrivacyOptOut\Facades\UsPrivacyOptOut;
use JeffersonGoncalves\UsPrivacyOptOut\Models\OptOutRecord;
use JeffersonGoncalves\UsPrivacyOptOut\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::create(['name' => 'Mary']);
    $this->travelTo(CarbonImmutable::parse('2026-11-17'));
});

/** Headers of a visitor from a US state (Cloudflare visitor location headers). */
function from(string $state): array
{
    return ['cf-ipcountry' => 'US', 'cf-region-code' => $state];
}

it('opts out and back in, keeping the full history', function () {
    Event::fake([OptOutRecorded::class]);

    expect($this->user->hasOptedOut(OptOutRight::Sale))->toBeFalse();

    $this->user->optOut(OptOutRight::Sale, 'form');

    expect(UsPrivacyOptOut::allows($this->user, OptOutRight::Sale, UsPrivacy::state('VA')))->toBeFalse()
        ->and(UsPrivacyOptOut::allows($this->user, OptOutRight::TargetedAdvertising, UsPrivacy::state('VA')))->toBeTrue();

    $this->user->optIn(OptOutRight::Sale, 'form');

    expect(UsPrivacyOptOut::allows($this->user, OptOutRight::Sale, UsPrivacy::state('VA')))->toBeTrue()
        ->and(UsPrivacyOptOut::history($this->user)->pluck('action')->all())->toBe([OptOutAction::OptedOut, OptOutAction::OptedIn]);

    Event::assertDispatchedTimes(OptOutRecorded::class, 2);
});

it('needs consent for sensitive data where the state says so', function () {
    $va = UsPrivacy::state('VA');
    $ut = UsPrivacy::state('UT');

    expect(UsPrivacyOptOut::allows($this->user, OptOutRight::SensitiveData, $va))->toBeFalse()
        ->and(UsPrivacyOptOut::allows($this->user, OptOutRight::SensitiveData, $ut))->toBeTrue()
        ->and(UsPrivacyOptOut::allows($this->user, OptOutRight::SensitiveData, null))->toBeTrue();

    $this->user->optIn(OptOutRight::SensitiveData, 'form');

    expect(UsPrivacyOptOut::allows($this->user, OptOutRight::SensitiveData, $va))->toBeTrue()
        // Maryland: only what is strictly necessary, consent or not.
        ->and(UsPrivacyOptOut::allows($this->user, OptOutRight::SensitiveData, UsPrivacy::state('MD')))->toBeFalse();

    $this->user->optOut(OptOutRight::SensitiveData, 'form');

    expect(UsPrivacyOptOut::allows($this->user, OptOutRight::SensitiveData, $va))->toBeFalse()
        ->and(UsPrivacyOptOut::allows($this->user, OptOutRight::SensitiveData, $ut))->toBeFalse();
});

it('honors Global Privacy Control in the states that require it', function () {
    Route::get('/page', fn () => 'ok')->middleware(['web', 'us-privacy.gpc']);

    // Virginia does not require it: nothing recorded.
    $this->withHeaders(['Sec-GPC' => '1', ...from('VA')])->get('/page')->assertOk();
    expect(OptOutRecord::count())->toBe(0);

    $response = $this->withHeaders(['Sec-GPC' => '1', ...from('CO')])->get('/page')->assertOk();
    $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === 'us_privacy_opt_out_id');

    expect(OptOutRecord::pluck('right')->all())->toBe([OptOutRight::Sale, OptOutRight::TargetedAdvertising])
        ->and(OptOutRecord::first())->source->toBe('gpc')->state->toBe('CO');

    $this->withHeaders(['Sec-GPC' => '1', ...from('CO')])->withUnencryptedCookie('us_privacy_opt_out_id', $cookie->getValue())->get('/page');

    expect(OptOutRecord::count())->toBe(2);
});

it('honors the signal everywhere, or never, by config', function () {
    Route::get('/page', fn () => 'ok')->middleware(['web', 'us-privacy.gpc']);

    config()->set('us-privacy-opt-out.honor_gpc', 'never');
    $this->actingAs($this->user)->withHeaders(['Sec-GPC' => '1', ...from('CO')])->get('/page');
    expect(OptOutRecord::count())->toBe(0);

    config()->set('us-privacy-opt-out.honor_gpc', 'always');
    $this->withHeaders(['Sec-GPC' => '1', ...from('VA')])->get('/page');
    expect(UsPrivacyOptOut::hasOptedOut($this->user, OptOutRight::TargetedAdvertising))->toBeTrue();
});

it('decides for the visitor of a request', function () {
    $request = fn (array $headers) => tap(Request::create('/'), fn (Request $r) => $r->headers->add($headers))->setUserResolver(fn () => $this->user);

    expect(UsPrivacyOptOut::allowsForRequest($request(from('TX')), OptOutRight::TargetedAdvertising))->toBeTrue()
        ->and(UsPrivacyOptOut::allowsForRequest($request(['Sec-GPC' => '1', ...from('TX')]), OptOutRight::TargetedAdvertising))->toBeFalse()
        ->and(UsPrivacyOptOut::allowsForRequest($request(['Sec-GPC' => '1', ...from('VA')]), OptOutRight::TargetedAdvertising))->toBeTrue()
        ->and(UsPrivacyOptOut::allowsForRequest($request(from('TX')), OptOutRight::SensitiveData))->toBeFalse()
        // Outside the US no state law applies.
        ->and(UsPrivacyOptOut::allowsForRequest($request(['cf-ipcountry' => 'BR']), OptOutRight::SensitiveData))->toBeTrue();
});

it('stores choices and consent from the endpoint, with the state', function () {
    $this->actingAs($this->user)
        ->withHeaders(from('OR'))
        ->postJson('/us-privacy/opt-out', ['right' => 'sensitive_data', 'opted_out' => false])
        ->assertCreated()
        ->assertJson(['action' => 'opted_in', 'state' => 'OR']);

    expect(UsPrivacyOptOut::allows($this->user, OptOutRight::SensitiveData, UsPrivacy::state('OR')))->toBeTrue();

    $this->postJson('/us-privacy/opt-out', ['right' => 'nope', 'opted_out' => true])->assertUnprocessable();
});

it('claims a browser opt-out on login', function () {
    UsPrivacyOptOut::optOut('browser-1', OptOutRight::Sale, 'gpc', 'CO');

    request()->cookies->set('us_privacy_opt_out_id', 'browser-1');
    event(new Login('web', $this->user, false));

    expect(UsPrivacyOptOut::latest($this->user, OptOutRight::Sale))
        ->source->toBe('claimed')
        ->state->toBe('CO')
        ->metadata->toMatchArray(['anonymous_id' => 'browser-1']);

    expect(UsPrivacyOptOut::claim('browser-1', $this->user))->toBeEmpty();
});

it('hides ad tags from visitors who opted out or send a signal that must be honored', function () {
    $view = "@usPrivacyAllows('targeted_advertising') pixel @else none @endusPrivacyAllows";
    $this->actingAs($this->user);

    expect(trim(Blade::render($view)))->toBe('pixel');

    $this->user->optOut(OptOutRight::TargetedAdvertising);

    expect(trim(Blade::render($view)))->toBe('none');
});

it('is append-only but can be deidentified', function () {
    $record = UsPrivacyOptOut::optOut($this->user, OptOutRight::Sale);

    expect(fn () => $record->update(['action' => 'opted_in']))->toThrow(LogicException::class)
        ->and(fn () => $record->delete())->toThrow(LogicException::class);

    $fresh = $record->fresh();
    $fresh->anonymizePersonalData();

    expect($fresh->fresh()->ip_address)->toBeNull();
});
