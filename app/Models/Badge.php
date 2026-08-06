<?php

namespace App\Models;

use Database\Factories\BadgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    /** @use HasFactory<BadgeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'icon',
    ];

    /**
     * Get the users this badge has been awarded to.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(BadgeUser::class)
            ->withPivot('awarded_at')
            ->withTimestamps();
    }
}
