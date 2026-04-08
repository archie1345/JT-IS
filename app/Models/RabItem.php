<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RabItem extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $table = 'rab_items';

    protected $fillable = [
        'rab_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'rab_id' => 'integer',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function rab(): BelongsTo
    {
        return $this->belongsTo(Rab::class);
    }
}
