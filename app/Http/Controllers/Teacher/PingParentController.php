<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Notifications\ParentPinged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PingParentController extends Controller
{
    /**
     * Email the student's parent/guardian on file about this session.
     * Parents have no account in the app, so this is an on-demand
     * notification (routed straight to their email), not a notify() call
     * on a User model.
     */
    public function __invoke(Request $request, ClassSession $classSession): RedirectResponse
    {
        abort_unless($classSession->teacher_id === $request->user()->id, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $classSession->loadMissing('student.studentProfile');

        $parentEmail = $classSession->student->studentProfile?->parent_email;

        if (! $parentEmail) {
            return back()->with('error', "No parent email on file for {$classSession->student->name}.");
        }

        Notification::route('mail', $parentEmail)->notify(new ParentPinged($classSession, $data['message']));

        return back()->with('success', "Parent notified about {$classSession->student->name}'s session.");
    }
}
