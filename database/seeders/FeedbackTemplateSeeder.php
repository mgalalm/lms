<?php

namespace Database\Seeders;

use App\Enums\FeedbackSentiment;
use App\Models\FeedbackTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds a starter set of feedback templates spanning all three sentiment
 * levels, so the teacher feedback form's template picker isn't empty out
 * of the box. Uses updateOrCreate (keyed by label) so re-running this
 * after editing a template's body fixes the existing row too.
 */
class FeedbackTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'label' => 'Excellent Work',
                'body' => 'This is outstanding work! You clearly put a lot of thought and effort into this project, and it shows. Keep up this level of quality.',
                'sentiment' => FeedbackSentiment::Positive,
                'sort_order' => 1,
            ],
            [
                'label' => 'Creative Solution',
                'body' => "I really like the creative approach you took here — it's not the way most students solve this, and it works well.",
                'sentiment' => FeedbackSentiment::Positive,
                'sort_order' => 2,
            ],
            [
                'label' => 'Solid Effort',
                'body' => "Good, solid work overall. You've clearly understood the core concept — nice job.",
                'sentiment' => FeedbackSentiment::Neutral,
                'sort_order' => 3,
            ],
            [
                'label' => 'On the Right Track',
                'body' => "You're on the right track with this one. A bit more polish and it'll really shine.",
                'sentiment' => FeedbackSentiment::Neutral,
                'sort_order' => 4,
            ],
            [
                'label' => 'Needs More Detail',
                'body' => 'This is a good start, but it needs more detail to fully meet the assignment brief. Take another look at the requirements and try adding a bit more depth.',
                'sentiment' => FeedbackSentiment::NeedsImprovement,
                'sort_order' => 5,
            ],
            [
                'label' => 'Please Revise and Resubmit',
                'body' => "This submission is missing a few key pieces from the brief. Please review the assignment again and resubmit when you've had a chance to address them.",
                'sentiment' => FeedbackSentiment::NeedsImprovement,
                'sort_order' => 6,
            ],
        ];

        foreach ($templates as $template) {
            FeedbackTemplate::updateOrCreate(
                ['label' => $template['label']],
                [
                    'body' => $template['body'],
                    'sentiment' => $template['sentiment'],
                    'sort_order' => $template['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
