<?php

namespace App\Services\Sms;

interface SmsProvider
{
    /**
     * Send a text message to an E.164-formatted number.
     */
    public function send(string $toE164, string $message): SmsResult;
}
