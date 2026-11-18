<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight;
use JeffersonGoncalves\UsPrivacyOptOut\Models\OptOutRecord;
use JeffersonGoncalves\UsPrivacyOptOut\OptOutManager;

/**
 * @mixin Model
 */
trait HasOptOuts
{
    /**
     * @return MorphMany<OptOutRecord, $this>
     */
    public function optOuts(): MorphMany
    {
        return $this->morphMany(OptOutRecord::class, 'subject');
    }

    public function optOut(OptOutRight $right, ?string $source = null): OptOutRecord
    {
        return app(OptOutManager::class)->optOut($this, $right, $source);
    }

    public function optIn(OptOutRight $right, ?string $source = null): OptOutRecord
    {
        return app(OptOutManager::class)->optIn($this, $right, $source);
    }

    public function hasOptedOut(OptOutRight $right): bool
    {
        return app(OptOutManager::class)->hasOptedOut($this, $right);
    }
}
