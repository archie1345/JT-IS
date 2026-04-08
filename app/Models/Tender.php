<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tender extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'value',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }
}
