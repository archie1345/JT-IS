<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectCostItem extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'project_cost_id',
        'source_type',
        'source_item_id',
        'category',
        'description',
        'unit',
        'quantity',
        'unit_price',
        'total_price',
        'vendor',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'project_cost_id' => 'integer',
            'source_item_id' => 'integer',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function projectCost(): BelongsTo
    {
        return $this->belongsTo(ProjectCost::class);
    }
}
