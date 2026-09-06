<?php

namespace App\Jobs;

use App\Enums\ParentContactChannel;
use App\Enums\ParentNotificationType;
use App\Enums\ParentPingDeliveryStatus;
use App\Models\ClassSession;
use App\Models\ParentPingLog;
use App\Models\User;
use App\Notifications\ParentPinged;
use App\Services\Parents\ParentNotificationRouter;
use App\Services\Parents\ResolvedParentChannel;
use App\Services\Sms\SmsProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

/**
 * Sends and logs one "Ping Parent" attempt. Rate limiting happens in
 * PingParentController *before* this is dispatched — a queued job can't
 * hand the teacher an immediate flash message, so the limit check has to
 * happen synchronously in the request. This job only sends and logs.
 */
class SendParentPingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClassSession $classSession,
        public User $triggeredBy,
        public string $message,
    ) {}

    public function handle(ParentNotificationRouter $router, SmsProvider $sms): void
    {
        $this->classSession->loadMissing(['student.parentContact', 'lesson.module.course']);

        $contact = $this->classSession->student->parentContact;

        if (! $contact) {
            $this->log(null, ParentPingDeliveryStatus::NoVerifiedContact, null, null, null, null, 'No parent contact on file.');

            return;
        }

        foreach ($router->resolve($contact, ParentNotificationType::UrgentPing) as $resolved) {
            $this->deliver($contact->id, $resolved, $sms);
        }
    }

    private function deliver(int $parentContactId, ResolvedParentChannel $resolved, SmsProvider $sms): void
    {
        if (! $resolved->isDeliverable()) {
            $this->log($parentContactId, ParentPingDeliveryStatus::NoVerifiedContact, null, null, null, null, 'No verified contact point for urgent_ping.');

            return;
        }

        if ($resolved->channel === ParentContactChannel::Email) {
            Notification::route('mail', $resolved->destination)->notify(new ParentPinged($this->classSession, $this->message));

            $this->log($parentContactId, ParentPingDeliveryStatus::Sent, $resolved->channel, $resolved->destination, null, null, null);

            return;
        }

        $result = $sms->send($resolved->destination, $this->renderSms());

        $this->log(
            $parentContactId,
            $result->successful ? ParentPingDeliveryStatus::Sent : ParentPingDeliveryStatus::Failed,
            $resolved->channel,
            $resolved->destination,
            $result->providerMessageId,
            $result->status,
            $result->errorDetail,
        );
    }

    private function renderSms(): string
    {
        $lessonPath = collect([
            $this->classSession->lesson->module?->course?->name,
            $this->classSession->lesson->module?->title,
            $this->classSession->lesson->title,
        ])->filter()->join(' / ');

        $time = $this->classSession->scheduled_at
            ->copy()
            ->setTimezone($this->classSession->student->resolvedTimezone())
            ->format('M j, g:i A');

        return strtr(config('parent_notifications.ping.sms'), [
            ':student' => $this->classSession->student->name,
            ':lesson' => $lessonPath,
            ':time' => $time,
        ]);
    }

    private function log(
        ?int $parentContactId,
        ParentPingDeliveryStatus $status,
        ?ParentContactChannel $channel,
        ?string $destination,
        ?string $providerMessageId,
        ?string $providerStatus,
        ?string $errorDetail,
    ): void {
        ParentPingLog::create([
            'class_session_id' => $this->classSession->id,
            'student_id' => $this->classSession->student_id,
            'triggered_by_user_id' => $this->triggeredBy->id,
            'parent_contact_id' => $parentContactId,
            'channel' => $channel,
            'destination' => $destination,
            'status' => $status,
            'provider_message_id' => $providerMessageId,
            'provider_status' => $providerStatus,
            'message' => $channel === ParentContactChannel::Phone ? $this->renderSms() : $this->message,
            'error_detail' => $errorDetail,
        ]);
    }
}
