<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\TeacherRequestType;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherRequestResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequestsController extends Controller
{
    public function index(Request $request): Response
    {
        $teacherProfile = $request->user()->teacherProfile;
        $type = $request->string('type', TeacherRequestType::Leave->value)->value();

        $history = $teacherProfile->requests()
            ->where('type', $type)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $leaveCredit = $teacherProfile->leaveCredit;

        return Inertia::render('teacher/Requests/Index', [
            'type' => $type,
            'history' => TeacherRequestResource::collection($history)->response()->getData(true),
            'leaveCredit' => [
                'monthly_limit' => $leaveCredit->monthly_limit ?? 5,
                'monthly_used' => $leaveCredit->monthly_used ?? 0,
                'annual_limit' => $leaveCredit->annual_limit ?? 15,
                'annual_used' => $leaveCredit->annual_used ?? 0,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->enum('type', TeacherRequestType::class);

        abort_if($type === null, 422, 'Invalid request type.');

        $rules = match ($type) {
            TeacherRequestType::Leave => [
                'leave_type' => ['required', 'in:planned,emergency,sick'],
                'sub_reason' => ['required', 'string', 'max:255'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'notes' => ['nullable', 'string'],
                'attachment' => ['nullable', 'file', 'max:5120'],
            ],
            TeacherRequestType::ExcusedTime => [
                'start_date' => ['required', 'date'],
                'notes' => ['nullable', 'string'],
            ],
            TeacherRequestType::HrRequest, TeacherRequestType::Support => [
                'sub_reason' => ['required', 'string', 'max:255'],
                'notes' => ['required', 'string'],
            ],
            TeacherRequestType::Resignation => [
                'start_date' => ['required', 'date'],
                'notes' => ['nullable', 'string'],
            ],
        };

        $data = $request->validate($rules);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('request-attachments');
        }

        $request->user()->teacherProfile->requests()->create([
            'type' => $type,
            'payload' => collect($data)->except('attachment')->all(),
            'attachment_path' => $attachmentPath,
        ]);

        return back()->with('success', ucfirst(str_replace('_', ' ', $type->value)).' request submitted');
    }
}
