<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadOccasion extends Model
{
    protected $fillable = [
        'lead_id', 'title', 'occasion_type', 'occasion_date', 'is_recurring', 'notes',
    ];

    protected $casts = [
        'occasion_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public const TYPES = [
        'birthday' => 'عيد ميلاد',
        'anniversary' => 'ذكرى سنوية',
        'other' => 'مناسبة أخرى',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** هل المناسبة اليوم؟ */
    public function isToday(): bool
    {
        if (! $this->is_recurring) {
            return $this->occasion_date->isToday();
        }

        return $this->occasion_date->format('m-d') === now()->format('m-d');
    }

    /** هل المناسبة غداً؟ */
    public function isTomorrow(): bool
    {
        if (! $this->is_recurring) {
            return $this->occasion_date->isTomorrow();
        }

        return $this->occasion_date->format('m-d') === now()->addDay()->format('m-d');
    }
}
