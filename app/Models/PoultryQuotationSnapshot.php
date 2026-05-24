<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoultryQuotationSnapshot extends Model
{
    use HasFactory;

    protected $table = 'poultry_quotation_snapshots';

    protected $fillable = [
        'poultry_quotation_id',
        'parameters',
        'inputs',
        'results',
    ];

    protected $casts = [
        'parameters' => 'array',
        'inputs' => 'array',
        'results' => 'array',
    ];

    public function poultryQuotation(): BelongsTo
    {
        return $this->belongsTo(PoultryQuotation::class, 'poultry_quotation_id');
    }

    public static function fromCalculation(PoultryQuotation $quotation, array $params, array $inputs, array $results): self
    {
        return static::create([
            'poultry_quotation_id' => $quotation->id,
            'parameters' => $params,
            'inputs' => $inputs,
            'results' => $results,
        ]);
    }
}
