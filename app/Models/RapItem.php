<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RapItem extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $table = 'rap_items';

    protected $fillable = [
        'rap_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'spec_brand',
        'spec_size',
        'spec_strength',
    ];

    protected function casts(): array
    {
        return [
            'rap_id' => 'integer',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function rap(): BelongsTo
    {
        return $this->belongsTo(Rap::class);
    }
}
