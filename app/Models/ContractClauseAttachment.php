<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ContractClauseAttachment extends Pivot
{
    public $incrementing = true;

    protected $table = 'contract_clause_attachments';

    protected $fillable = [
        'contract_id', 'contract_clause_id',
        'content_override', 'variables_values', 'items',
        'sort_order', 'is_visible', 'notes',
    ];

    protected $casts = [
        'variables_values' => 'array',
        'items' => 'array',
        'is_visible' => 'boolean',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function clause(): BelongsTo
    {
        return $this->belongsTo(ContractClause::class, 'contract_clause_id');
    }

    /**
     * النص النهائي بعد استبدال المتغيرات
     */
    public function getRenderedContentAttribute(): string
    {
        $content = $this->content_override ?? $this->clause->content;
        $variables = $this->variables_values ?? [];

        // استبدال المتغيرات الخاصة بالبند
        foreach ($variables as $key => $value) {
            $content = str_replace('{{'.$key.'}}', (string) $value, $content);
        }

        // متغيرات العقد العامة
        if ($this->contract) {
            $contractVars = [
                'CONTRACT_NUMBER' => $this->contract->contract_number,
                'CUSTOMER_NAME' => $this->contract->customer->name ?? '',
                'CONTRACT_DATE' => $this->contract->contract_date?->format('Y/m/d'),
                'TOTAL_VALUE' => number_format((float) $this->contract->total_value, 0),
                'CURRENCY' => $this->contract->currency,
                'DELIVERY_DATE' => $this->contract->expected_delivery_date?->format('Y/m/d'),
                'INSTALLATION_LOCATION' => $this->contract->installation_location,
            ];
            foreach ($contractVars as $key => $value) {
                $content = str_replace('{{'.$key.'}}', (string) $value, $content);
            }
        }

        return $content;
    }
}
