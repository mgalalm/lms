<?php

namespace App\Http\Controllers;

use App\Services\Referrals\ReferralRedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralCodeValidationController extends Controller
{
    /**
     * Live-validate a referral code as it's typed at registration or
     * checkout, without redeeming it. Route-throttled (see routes/
     * referral-codes.php) since this is the endpoint someone would use to
     * brute-force guess a valid code.
     */
    public function __invoke(Request $request, ReferralRedemptionService $referrals): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:255']]);

        $referralCode = $referrals->findRedeemableFor($request->user(), $request->string('code')->upper()->value());

        return response()->json([
            'valid' => $referralCode !== null,
            'reward_sessions' => $referralCode?->reward_sessions_referee,
        ]);
    }
}
