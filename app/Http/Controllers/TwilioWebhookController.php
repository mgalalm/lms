<?php

namespace App\Http\Controllers;

use App\Models\ParentPingLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Twilio's status-callback webhook — asynchronously updates a ping's
 * delivery status (queued -> sent -> delivered, or failed) after the
 * initial send. Optional; a ping still records its outcome from the
 * synchronous SmsResult even if this never fires.
 */
class TwilioWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $messageSid = $request->input('MessageSid');
        $status = $request->input('MessageStatus');

        if ($messageSid && $status) {
            ParentPingLog::where('provider_message_id', $messageSid)->update(['provider_status' => $status]);
        }

        return response('', 204);
    }
}
