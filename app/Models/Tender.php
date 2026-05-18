<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tender extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'title',
        'document_number',
        'document_date',
        'owner',
        'location',
        'value',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'document_date' => 'date',
            'value' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
