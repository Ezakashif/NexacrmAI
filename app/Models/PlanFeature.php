<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasFactory;
    public const TYPES = [
        'boolean',
        'text',
        'number',
        'limit',
        'option',
    ];

    protected $fillable = [
        'feature_key',
        'feature_name',
        'description',
        'feature_type',
        'feature_value',
        'sort_order',
        'is_highlighted',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_highlighted' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
