<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\UsPrivacyOptOut\UsPrivacyOptOut
 */
class UsPrivacyOptOut extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-us-privacy-opt-out';
    }
}
