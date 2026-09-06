<?php

namespace App\Services\Parents;

use App\Enums\ParentContactChannel;

/**
 * One deliverable outcome of ParentNotificationRouter::resolve() — either a
 * channel + destination to actually send through, or a reason nothing
 * could be resolved.
 */
final readonly class ResolvedParentChannel
{
    private function __construct(
        public ?ParentContactChannel $channel,
        public ?string $destination,
        public string $reason,
    ) {}

    public static function to(ParentContactChannel $channel, string $destination, string $reason = 'preferred'): self
    {
        return new self($channel, $destination, $reason);
    }

    public static function none(string $reason): self
    {
        return new self(null, null, $reason);
    }

    public function isDeliverable(): bool
    {
        return $this->channel !== null;
    }
}
