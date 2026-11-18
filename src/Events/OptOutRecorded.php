<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Events;

use JeffersonGoncalves\UsPrivacyOptOut\Models\OptOutRecord;

/**
 * A consumer opted out or back in. On an opt-out of sale/sharing, stop selling or sharing their
 * personal information and tell the third parties you sold or shared it to (US-PRIVACY Regulations §7026(f)).
 */
class OptOutRecorded
{
    public function __construct(public OptOutRecord $record) {}
}
