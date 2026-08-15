<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\OpenAI\ModerationClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModerateMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * A stuck/misbehaving moderation call must not leave a message
     * invisible forever — after this many attempts, failed() hides it
     * for manual review instead of retrying indefinitely.
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Message $message)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ModerationClient $moderationClient): void
    {
        $text = trim(strip_tags($this->message->body ?? ''));

        if ($text === '') {
            $this->message->approveModeration();

            return;
        }

        if (! config('services.openai.key')) {
            Log::warning('Skipped message moderation: no OpenAI API key configured.', [
                'message_id' => $this->message->id,
            ]);

            $this->message->approveModeration();

            return;
        }

        try {
            $result = $moderationClient->moderate($text);
        } catch (Throwable $e) {
            Log::error('Message moderation check failed; hiding message as a precaution.', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
            ]);

            $this->message->flagModeration(['moderation_check_failed']);

            return;
        }

        if ($result['flagged']) {
            $this->message->flagModeration($result['categories']);

            return;
        }

        $this->message->approveModeration();
    }

    /**
     * If the job itself fails outright (e.g. after exhausting retries),
     * fail safe: keep the message hidden and flagged rather than letting
     * it sit unresolved and invisible with no one alerted.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Message moderation job failed permanently; flagging for manual review.', [
            'message_id' => $this->message->id,
            'error' => $exception->getMessage(),
        ]);

        $this->message->flagModeration(['moderation_job_failed']);
    }
}
