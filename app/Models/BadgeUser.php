<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BadgeUser extends Pivot
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
        ];
    }
}
