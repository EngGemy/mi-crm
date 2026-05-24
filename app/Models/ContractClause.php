<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractClause extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'title', 'title_en', 'category',
        'content', 'content_en',
        'variables', 'items_schema',
        'is_required', 'is_default', 'is_active',
        'sort_order', 'applicable_contract_types', 'description',
    ];

    protected $casts = [
        'variables' => 'array',
        'items_schema' => 'array',
        'applicable_contract_types' => 'array',
        'is_required' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const CATEGORIES = [
        'preamble' => 'ديباجة',
        'subject' => 'موضوع العقد',
        'financial' => 'البند المالي',
        'schedule' => 'الجدول الزمني',
        'technical' => 'المواصفات الفنية',
        'construction' => 'الإنشاءات',
        'electricity' => 'الكهرباء',
        'plumbing' => 'السباكة',
        'isolation' => 'العزل',
        'fire_safety' => 'السلامة والإطفاء',
        'generator' => 'الجنريتور',
        'security' => 'نظام الأمن',
        'installation' => 'التركيب',
        'training' => 'التدريب',
        'warranty' => 'الضمان',
        'maintenance' => 'الصيانة',
        'spare_parts' => 'قطع الغيار',
        'force_majeure' => 'القوة القاهرة',
        'penalties' => 'الشروط الجزائية',
        'confidentiality' => 'السرية',
        'jurisdiction' => 'الاختصاص القضائي',
        'general' => 'أحكام عامة',
        'custom' => 'بند مخصص',
    ];

    /**
     * Scope: البنود النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'contract_clause_attachments')
            ->using(ContractClauseAttachment::class)
            ->withPivot([
                'content_override',
                'variables_values',
                'items',
                'sort_order',
                'is_visible',
                'notes',
            ])
            ->withTimestamps();
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * استخراج المتغيرات من النص {{var_name}}
     */
    public function extractVariableNames(): array
    {
        preg_match_all('/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/', $this->content, $matches);

        return array_unique($matches[1] ?? []);
    }
}
