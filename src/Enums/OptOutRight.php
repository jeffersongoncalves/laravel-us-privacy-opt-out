<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Enums;

enum OptOutRight: string
{
    /** Opt out of the sale of personal data (every state). */
    case Sale = 'sale';

    /** Opt out of targeted advertising (every state). */
    case TargetedAdvertising = 'targeted_advertising';

    /** Opt out of profiling in furtherance of decisions with legal or similarly significant effects. */
    case Profiling = 'profiling';

    /**
     * Processing of sensitive data. In the opt-in states OptedIn is the consumer's consent and
     * OptedOut withdraws it; in Utah and Iowa OptedOut is the right to opt out.
     */
    case SensitiveData = 'sensitive_data';

    /** The rights a universal opt-out signal (Global Privacy Control) exercises. */
    public static function signalled(): array
    {
        return [self::Sale, self::TargetedAdvertising];
    }
}
