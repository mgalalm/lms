<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\SettingKey;
use App\Http\Controllers\Controller;
use App\Jobs\SendParentPingJob;
use App\Models\ClassSession;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class PingParentController extends Controller
{
    private const STUDENT_COOLDOWN_SECONDS = 1800;

    /**
     * Queue a notification to the student's parent/guardian about this
     * session. Rate limiting happens here, before the job is dispatched,
     * so the teacher gets an immediate flash message rather than a queued
     * job failing silently in the background. The job itself only sends
     * (respecting channel preference + verification) and logs.
     */
    public function __invoke(Request $request, ClassSession $classSession): RedirectResponse
    {
        Gate::authorize('pingParent', $classSession);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $classSession->loadMissing('student.parentContact');

        if (! $classSession->student->parentContact) {
            return back()->with('error', "No parent contact on file for {$classSession->student->name}.");
        }

        $studentKey = "ping-parent:student:{$classSession->student_id}";
        $teacherKey = 'ping-parent:teacher:'.$request->user()->id.':'.now()->toDateString();
        $dailyCap = Setting::get(SettingKey::ParentPingDailyCapPerTeacher);

        if (RateLimiter::tooManyAttempts($studentKey, 1)) {
            $minutes = (int) ceil(RateLimiter::availableIn($studentKey) / 60);

            return back()->with('error', "This student's parent was already pinged recently. Try again in {$minutes} minute(s).");
        }

        if (RateLimiter::tooManyAttempts($teacherKey, $dailyCap)) {
            return back()->with('error', "You've reached today's limit of {$dailyCap} parent pings.");
        }

        RateLimiter::hit($studentKey, self::STUDENT_COOLDOWN_SECONDS);
        RateLimiter::hit($teacherKey, 86400);

        SendParentPingJob::dispatch($classSession, $request->user(), $data['message']);

        return back()->with('success', "Ping queued for {$classSession->student->name}'s parent.");
    }
}
