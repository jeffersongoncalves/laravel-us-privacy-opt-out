<?php

namespace JeffersonGoncalves\UsPrivacyOptOut\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use JeffersonGoncalves\UsPrivacyOptOut\Enums\OptOutRight;
use JeffersonGoncalves\UsPrivacyOptOut\OptOutManager;

/**
 * Endpoint for your opt-out links ("Your Privacy Choices") and the sensitive data consent:
 * POST {right, opted_out}. opted_out false on sensitive_data gives consent.
 */
class OptOutController
{
    public function __invoke(Request $request, OptOutManager $optOuts): JsonResponse
    {
        $data = $request->validate([
            'right' => ['required', Rule::enum(OptOutRight::class)],
            'opted_out' => ['required', 'boolean'],
        ]);

        $right = OptOutRight::from($data['right']);
        $record = $optOuts->recordForRequest($request, $right, $request->boolean('opted_out'), 'form');

        return response()->json(['right' => $right->value, 'action' => $record->action->value, 'state' => $record->state], 201);
    }
}
