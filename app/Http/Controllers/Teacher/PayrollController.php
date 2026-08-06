<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayrollEntryResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $teacherProfile = $request->user()->teacherProfile;

        $entries = $teacherProfile->payrollEntries()
            ->with('adjustments')
            ->orderByDesc('month')
            ->get();

        return Inertia::render('teacher/Payroll', [
            'entries' => PayrollEntryResource::collection($entries),
            'bank' => [
                'name' => $teacherProfile->bank_name,
                'account_number' => $teacherProfile->bank_account_number,
            ],
        ]);
    }
}
