<?php

return [

    'table' => 'us_privacy_opt_outs',

    /*
     * Store IP address and user agent with each choice, to show when and how the consumer
     * opted out or consented.
     */
    'store_request' => true,

    /*
     * Global Privacy Control (Sec-GPC: 1) as an opt-out of sale and targeted advertising:
     * "required" in the states whose law requires honoring universal opt-out mechanisms, from
     * the date it does (see laravel-us-privacy), "always" everywhere, or "never".
     * Applied by the `us-privacy.gpc` middleware and by allowsForRequest().
     */
    'honor_gpc' => 'required',

    /*
     * Optional endpoint for your "Your Privacy Choices" links and the sensitive data consent:
     * POST {right, opted_out}. Visitors are identified by an encrypted cookie holding a random id.
     */
    'route' => [
        'enabled' => false,
        'path' => 'us-privacy/opt-out',
        'middleware' => ['web', 'throttle:60,1'],
        'cookie' => 'us_privacy_opt_out_id',
        // On login, copy the visitor's latest choices (by cookie id) onto the user.
        'claim_on_login' => true,
    ],

];
