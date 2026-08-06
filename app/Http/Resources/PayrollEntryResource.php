<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollEntryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'month' => $this->month->format('M Y'),
            'organization' => $this->organization,
            'sessions_count' => $this->sessions_count,
            'basic_salary' => (float) $this->basic_salary,
            'bonus_total' => $this->bonusTotal(),
            'deduction_total' => $this->deductionTotal(),
            'net_amount' => $this->netAmount(),
            'status' => $this->status->value,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'adjustments' => $this->whenLoaded('adjustments', fn () => $this->adjustments->map(fn ($adjustment) => [
                'type' => $adjustment->type->value,
                'amount' => (float) $adjustment->amount,
                'reason' => $adjustment->reason,
            ])),
        ];
    }
}
