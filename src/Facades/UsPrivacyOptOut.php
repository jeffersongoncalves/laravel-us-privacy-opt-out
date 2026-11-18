<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\UsPrivacyOptOut\OptOutManager;

/**
 * @method static \JeffersonGoncalves\UsPrivacyOptOut\Models\OptOutRecord optOut(\Illuminate\Database\Eloquent\Model|string $subject, \JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight $right, ?string $source = null, ?string $state = null, array<string, mixed> $metadata = [])
 * @method static \JeffersonGoncalves\UsPrivacyOptOut\Models\OptOutRecord optIn(\Illuminate\Database\Eloquent\Model|string $subject, \JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight $right, ?string $source = null, ?string $state = null, array<string, mixed> $metadata = [])
 * @method static bool hasOptedOut(\Illuminate\Database\Eloquent\Model|string|null $subject, \JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight $right)
 * @method static bool hasConsented(\Illuminate\Database\Eloquent\Model|string|null $subject, \JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight $right = \JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight::SensitiveData)
 * @method static bool allows(\Illuminate\Database\Eloquent\Model|string|null $subject, \JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight $right, ?\JeffersonGoncalves\UsPrivacy\StateRules $rules)
 * @method static bool allowsForRequest(\Illuminate\Http\Request $request, \JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight $right)
 * @method static bool honorsSignal(\Illuminate\Http\Request $request, ?\JeffersonGoncalves\UsPrivacy\StateRules $rules)
 * @method static \JeffersonGoncalves\UsPrivacyOptOut\Models\OptOutRecord recordForRequest(\Illuminate\Http\Request $request, \JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight $right, bool $optedOut, ?string $source = null)
 * @method static \JeffersonGoncalves\UsPrivacyOptOut\Models\OptOutRecord|null latest(\Illuminate\Database\Eloquent\Model|string $subject, \JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight $right)
 * @method static \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\UsPrivacyOptOut\Models\OptOutRecord> history(\Illuminate\Database\Eloquent\Model|string $subject, ?\JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight $right = null)
 * @method static \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\UsPrivacyOptOut\Models\OptOutRecord> claim(string $anonymousId, \Illuminate\Database\Eloquent\Model $subject)
 *
 * @see OptOutManager
 */
class UsPrivacyOptOut extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OptOutManager::class;
    }
}
