<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rap extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'total_budget',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'total_budget' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RapItem::class);
    }
}
