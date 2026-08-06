<?php

namespace App\Models;

use App\Enums\PayrollAdjustmentType;
use App\Enums\PayrollStatus;
use Database\Factories\PayrollEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollEntry extends Model
{
    /** @use HasFactory<PayrollEntryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_profile_id',
        'month',
        'organization',
        'sessions_count',
        'basic_salary',
        'status',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'date',
            'basic_salary' => 'decimal:2',
            'status' => PayrollStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the teacher this payroll entry belongs to.
     */
    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    /**
     * Get the itemized bonus/deduction adjustments for this month.
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    public function bonusTotal(): float
    {
        return (float) $this->adjustments()->where('type', PayrollAdjustmentType::Bonus)->sum('amount');
    }

    public function deductionTotal(): float
    {
        return (float) $this->adjustments()->where('type', PayrollAdjustmentType::Deduction)->sum('amount');
    }

    public function netAmount(): float
    {
        return (float) $this->basic_salary + $this->bonusTotal() - $this->deductionTotal();
    }
}
