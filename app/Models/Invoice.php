<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'invoice_number',
        'amount',
        'tax_amount',
        'invoice_date',
        'due_date',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
