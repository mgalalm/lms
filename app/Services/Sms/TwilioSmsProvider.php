<?php

namespace App\Services\Sms;

use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * The only class in the app allowed to reference the Twilio SDK — every
 * other consumer depends on the SmsProvider interface instead, so adding a
 * future provider (Vonage, MessageBird, WhatsApp) never touches call sites.
 */
class TwilioSmsProvider implements SmsProvider
{
    public function __construct(
        private readonly ?Client $client,
        private readonly ?string $from,
    ) {}

    public function send(string $toE164, string $message): SmsResult
    {
        if (! $this->client || ! $this->from) {
            return SmsResult::failure('SMS provider not configured', 'not_configured');
        }

        try {
            $sent = $this->client->messages->create($toE164, [
                'from' => $this->from,
                'body' => $message,
            ]);

            return SmsResult::success($sent->sid, $sent->status);
        } catch (TwilioException $e) {
            return SmsResult::failure($e->getMessage());
        }
    }
}
