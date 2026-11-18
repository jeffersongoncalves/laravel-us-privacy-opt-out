<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\UsPrivacy\Attributes\PersonalData;
use JeffersonGoncalves\UsPrivacy\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\UsPrivacy\Contracts\HasPersonalData;
use JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutAction;
use JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight;
use LogicException;

/**
 * One immutable opt-out or opt-in. The ledger is append-only: a new choice is a new row.
 * Only the personal information columns may change, so a consumer can still be deidentified.
 *
 * @property OptOutRight $right
 * @property OptOutAction $action
 * @property string|null $state the consumer's state (two-letter code) when they chose
 * @property string|null $source form, gpc, claimed...
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 */
#[PersonalData('ip_address', purpose: 'Record of the consumer request')]
#[PersonalData('user_agent', purpose: 'Record of the consumer request')]
class OptOutRecord extends Model implements HasPersonalData
{
    use InteractsWithPersonalData;

    public const UPDATED_AT = null;

    protected $guarded = [];

    public function getTable(): string
    {
        return config('us-privacy-opt-out.table', 'us_privacy_opt_outs');
    }

    protected function casts(): array
    {
        return [
            'right' => OptOutRight::class,
            'action' => OptOutAction::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (OptOutRecord $record): void {
            $allowed = array_map(fn (PersonalData $field): string => $field->column, static::personalDataFields());

            if (array_diff(array_keys($record->getDirty()), $allowed) !== []) {
                throw new LogicException('Opt-out records are append-only: record a new choice instead.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Opt-out records are append-only and cannot be deleted. Deidentify them instead.');
        });
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
