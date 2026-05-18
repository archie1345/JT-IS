<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rab extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'document_number',
        'document_date',
        'total_budget',
        'dpp_amount',
        'tax_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'document_date' => 'date',
            'total_budget' => 'decimal:2',
            'dpp_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RabItem::class);
    }
}
