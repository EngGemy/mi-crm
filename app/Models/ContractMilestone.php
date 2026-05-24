<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'code', 'title', 'description',
        'expected_date', 'actual_date', 'status',
        'sort_order', 'triggers_payment', 'notes', 'completed_by',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'actual_date' => 'date',
        'triggers_payment' => 'boolean',
    ];

    public const STATUSES = [
        'pending' => 'قيد الانتظار',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'delayed' => 'متأخر',
        'skipped' => 'تم تخطيه',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'milestone_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
