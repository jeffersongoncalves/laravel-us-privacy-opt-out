<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Http;

use Closure;
use Illuminate\Http\Request;
use JeffersonGoncalves\UsPrivacy\UsPrivacy;
use JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight;
use JeffersonGoncalves\UsPrivacyOptOut\OptOutManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * A Global Privacy Control signal is an opt-out of sale and targeted advertising in the states
 * that require honoring universal opt-out mechanisms (honor_gpc "required"), or everywhere
 * ("always"). Records it once for the user, or the browser, sending it.
 */
class HonorGlobalPrivacyControl
{
    public function __construct(protected OptOutManager $optOuts, protected UsPrivacy $privacy) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->optOuts->honorsSignal($request, $this->privacy->rulesForRequest($request))) {
            $subject = Visitor::resolve($request);

            foreach (OptOutRight::signalled() as $right) {
                if (! $this->optOuts->hasOptedOut($subject, $right)) {
                    $this->optOuts->optOut($subject, $right, 'gpc');
                }
            }

            $request->attributes->set('us_privacy_gpc', true);
        }

        return $next($request);
    }
}
