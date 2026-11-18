<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use JeffersonGoncalves\UsPrivacyOptOut\Concerns\HasOptOuts;

class User extends Authenticatable
{
    use HasOptOuts;

    protected $guarded = [];

    public $timestamps = false;
}
