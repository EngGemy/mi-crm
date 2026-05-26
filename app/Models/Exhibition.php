<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exhibition extends Model
{
    protected $fillable = [
        'name', 'location', 'start_date', 'end_date',
        'cost', 'goal', 'notes', 'status', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public const STATUSES = [
        'planned' => 'مخطط',
        'active' => 'جارٍ',
        'closed' => 'منتهٍ',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    // ============ ROI Methods ============

    public function leadsCount(): int
    {
        return $this->leads()->count();
    }

    public function quotationsCount(): int
    {
        return $this->leads()->whereNotNull('quotation_id')->count();
    }

    public function contractsCount(): int
    {
        return $this->leads()->whereNotNull('contract_id')->count();
    }

    public function contractsValue(): float
    {
        return (float) $this->leads()
            ->join('contracts', 'leads.contract_id', '=', 'contracts.id')
            ->sum('contracts.total_amount');
    }

    public function roiPercentage(): float
    {
        $cost = (float) $this->cost;
        if ($cost <= 0) {
            return 0;
        }

        return round((($this->contractsValue() - $cost) / $cost) * 100, 1);
    }

    public function conversionRate(): float
    {
        $total = $this->leadsCount();
        if ($total === 0) {
            return 0;
        }

        return round(($this->contractsCount() / $total) * 100, 1);
    }
}
