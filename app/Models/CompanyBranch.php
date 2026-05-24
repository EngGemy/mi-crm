<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name_ar', 'name_en',
        'address_ar', 'address_en',
        'phone', 'email',
        'is_main', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
