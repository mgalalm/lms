<?php

namespace App\Models;

use App\Enums\FeedbackSentiment;
use Database\Factories\FeedbackTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A reusable starting point for teacher feedback comments, grouped by
 * sentiment — picking one populates (not replaces) the feedback textarea.
 */
class FeedbackTemplate extends Model
{
    /** @use HasFactory<FeedbackTemplateFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'label',
        'body',
        'sentiment',
        'sort_order',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sentiment' => FeedbackSentiment::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
