<?php

namespace App\Providers;

use App\Services\Sms\SmsProvider;
use App\Services\Sms\TwilioSmsProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Twilio\Rest\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsProvider::class, function () {
            return match ($provider = config('services.sms.default')) {
                'twilio' => new TwilioSmsProvider(
                    $this->twilioClient(),
                    config('services.sms.providers.twilio.from'),
                ),
                default => throw new RuntimeException("Unsupported SMS provider: {$provider}"),
            };
        });
    }

    /**
     * Null when Twilio isn't configured — TwilioSmsProvider treats a null
     * client as "not configured" rather than throwing, matching
     * CreateZoomMeetingJob's fail-safe for missing external-service config.
     */
    private function twilioClient(): ?Client
    {
        $sid = config('services.sms.providers.twilio.sid');
        $token = config('services.sms.providers.twilio.token');

        if (! $sid || ! $token) {
            return null;
        }

        return new Client($sid, $token);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Inertia props are consumed directly by the frontend, not as a
        // JSON:API response, so resources shouldn't auto-wrap in "data".
        JsonResource::withoutWrapping();
    }
}
