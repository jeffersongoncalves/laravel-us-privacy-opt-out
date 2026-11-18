<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Enums;

enum OptOutAction: string
{
    case OptedOut = 'opted_out';
    case OptedIn = 'opted_in';
}
