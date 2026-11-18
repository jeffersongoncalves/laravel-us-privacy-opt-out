<?php

namespace JeffersonGoncalves\UsPrivacyOptOut;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JeffersonGoncalves\UsPrivacy\StateRules;
use JeffersonGoncalves\UsPrivacy\UsPrivacy;
use JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutAction;
use JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight;
use JeffersonGoncalves\UsPrivacyOptOut\Events\OptOutRecorded;
use JeffersonGoncalves\UsPrivacyOptOut\Http\Visitor;
use JeffersonGoncalves\UsPrivacyOptOut\Models\OptOutRecord;

/**
 * A subject is an Eloquent model (a consumer with an account) or a string anonymous id (a browser).
 */
class OptOutManager
{
    public function __construct(protected UsPrivacy $privacy) {}

    /**
     * @param  string|null  $state  the consumer's state; defaults to the one of the current request
     * @param  array<string, mixed>  $metadata
     */
    public function optOut(Model|string $subject, OptOutRight $right, ?string $source = null, ?string $state = null, array $metadata = []): OptOutRecord
    {
        return $this->record($subject, $right, OptOutAction::OptedOut, $source, $state, $metadata);
    }

    /**
     * The consumer opts back in or, for sensitive data, gives consent.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function optIn(Model|string $subject, OptOutRight $right, ?string $source = null, ?string $state = null, array $metadata = []): OptOutRecord
    {
        return $this->record($subject, $right, OptOutAction::OptedIn, $source, $state, $metadata);
    }

    public function hasOptedOut(Model|string|null $subject, OptOutRight $right): bool
    {
        return $subject !== null && $this->latest($subject, $right)?->action === OptOutAction::OptedOut;
    }

    public function hasConsented(Model|string|null $subject, OptOutRight $right = OptOutRight::SensitiveData): bool
    {
        return $subject !== null && $this->latest($subject, $right)?->action === OptOutAction::OptedIn;
    }

    /**
     * Whether you may process this consumer's data for $right under $rules (their state's law;
     * null when no state law applies). Sale, targeted advertising and profiling: unless they opted
     * out. Sensitive data: with their consent in the opt-in states, unless they opted out in Utah
     * and Iowa, and never beyond what is strictly necessary in Maryland.
     */
    public function allows(Model|string|null $subject, OptOutRight $right, ?StateRules $rules): bool
    {
        if ($right !== OptOutRight::SensitiveData || $rules === null) {
            return ! $this->hasOptedOut($subject, $right);
        }

        return match ($rules->sensitive) {
            'opt_out' => ! $this->hasOptedOut($subject, $right),
            'opt_in' => $this->hasConsented($subject, $right),
            default => false,
        };
    }

    /**
     * allows() for the visitor of a request: the logged-in user or the browser, the rules of their
     * state, and a Global Privacy Control signal counting as an opt-out of sale and targeted
     * advertising wherever it must be honored.
     */
    public function allowsForRequest(Request $request, OptOutRight $right): bool
    {
        $rules = $this->privacy->rulesForRequest($request);

        if (in_array($right, OptOutRight::signalled(), true) && $this->honorsSignal($request, $rules)) {
            return false;
        }

        $user = $request->user() ?? auth()->user();
        $subject = $user instanceof Model ? $user : $request->cookie((string) config('us-privacy-opt-out.route.cookie', 'us_privacy_opt_out_id'));

        return $this->allows(is_string($subject) || $subject instanceof Model ? $subject : null, $right, $rules);
    }

    /** Whether the request carries a Global Privacy Control signal you must (or chose to) honor. */
    public function honorsSignal(Request $request, ?StateRules $rules): bool
    {
        if ($request->header('Sec-GPC') !== '1') {
            return false;
        }

        return match ((string) config('us-privacy-opt-out.honor_gpc', 'required')) {
            'always' => true,
            'never' => false,
            default => $rules?->requiresUniversalOptOut() ?? false,
        };
    }

    public function latest(Model|string $subject, OptOutRight $right): ?OptOutRecord
    {
        return $this->query($subject)->where('right', $right->value)->latest('id')->first();
    }

    /**
     * @return Collection<int, OptOutRecord>
     */
    public function history(Model|string $subject, ?OptOutRight $right = null): Collection
    {
        return $this->query($subject)
            ->when($right !== null, fn (Builder $query) => $query->where('right', $right?->value))
            ->oldest('id')
            ->get();
    }

    /**
     * @return Builder<OptOutRecord>
     */
    public function query(Model|string $subject): Builder
    {
        $query = OptOutRecord::query();

        if (is_string($subject)) {
            return $query->where('anonymous_id', $subject);
        }

        return $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', (string) $subject->getKey());
    }

    /**
     * Carry a browser's choices over to the account it logs into. Append-only: for each right,
     * the browser's latest choice is copied as a new record (source "claimed") unless the account
     * already decided after it.
     *
     * @return Collection<int, OptOutRecord> the records created
     */
    public function claim(string $anonymousId, Model $subject): Collection
    {
        $claimed = new Collection;

        foreach ($this->query($anonymousId)->orderByDesc('id')->get()->unique(fn (OptOutRecord $r): string => $r->right->value) as $record) {
            $own = $this->latest($subject, $record->right);
            $ownOrder = $own === null ? null
                : ($own->source === 'claimed' ? (int) ($own->metadata['claimed_record_id'] ?? $own->getKey()) : (int) $own->getKey());

            if ($ownOrder !== null && $ownOrder >= (int) $record->getKey()) {
                continue;
            }

            $claimed->push($this->record($subject, $record->right, $record->action, 'claimed', $record->state, [
                'anonymous_id' => $anonymousId,
                'claimed_record_id' => $record->getKey(),
            ], [
                'ip_address' => $record->getAttribute('ip_address'),
                'user_agent' => $record->getAttribute('user_agent'),
            ]));
        }

        return $claimed;
    }

    /** Record the choice of the visitor of a request (logged-in user or browser). */
    public function recordForRequest(Request $request, OptOutRight $right, bool $optedOut, ?string $source = null): OptOutRecord
    {
        $subject = Visitor::resolve($request);

        return $optedOut ? $this->optOut($subject, $right, $source) : $this->optIn($subject, $right, $source);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $attributes
     */
    protected function record(Model|string $subject, OptOutRight $right, OptOutAction $action, ?string $source, ?string $state, array $metadata, array $attributes = []): OptOutRecord
    {
        $request = app()->bound('request') ? request() : null;
        $store = config('us-privacy-opt-out.store_request', true);

        $record = OptOutRecord::create([
            ...(is_string($subject)
                ? ['anonymous_id' => $subject]
                : ['subject_type' => $subject->getMorphClass(), 'subject_id' => (string) $subject->getKey()]),
            'right' => $right,
            'action' => $action,
            'state' => $state ?? ($request === null ? null : $this->privacy->stateFromRequest($request)),
            'source' => $source,
            'ip_address' => $store ? $request?->ip() : null,
            'user_agent' => $store ? $request?->userAgent() : null,
            'metadata' => $metadata === [] ? null : $metadata,
            ...$attributes,
        ]);

        event(new OptOutRecorded($record));

        return $record;
    }
}
