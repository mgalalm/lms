<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\SessionPackagePurchaseResource;
use App\Http\Resources\SessionPackageResource;
use App\Models\SessionPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionPackagesController extends Controller
{
    /**
     * The student-facing storefront: purchasable packages, current credit
     * balance, and past purchase history.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $purchases = $user->sessionPackagePurchases()
            ->with('sessionPackage')
            ->latest()
            ->get();

        return Inertia::render('student/SessionPackages', [
            'balance' => $user->sessionCreditsBalance(),
            'packages' => SessionPackageResource::collection(SessionPackage::purchasable()->get()),
            'purchases' => SessionPackagePurchaseResource::collection($purchases),
        ]);
    }
}
