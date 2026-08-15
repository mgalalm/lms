<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Http;

class ModerationClient
{
    private const API_URL = 'https://api.openai.com/v1/moderations';

    private const MODEL = 'omni-moderation-latest';

    /**
     * Run text through OpenAI's Moderation API (free to use) and report
     * whether it was flagged, along with which categories triggered it.
     *
     * @return array{flagged: bool, categories: list<string>}
     */
    public function moderate(string $input): array
    {
        $response = Http::withToken(config('services.openai.key'))
            ->post(self::API_URL, [
                'model' => self::MODEL,
                'input' => $input,
            ])
            ->throw();

        $result = $response->json('results.0');

        return [
            'flagged' => (bool) ($result['flagged'] ?? false),
            'categories' => collect($result['categories'] ?? [])
                ->filter()
                ->keys()
                ->values()
                ->all(),
        ];
    }
}
