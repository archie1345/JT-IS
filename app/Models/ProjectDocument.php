<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'document_number',
        'document_type',
        'component_type',
        'component_id',
        'progress_weight',
        'signatories',
        'name',
        'original_name',
        'path',
        'mime_type',
        'size',
        'ocr_text',
        'ocr_engine',
        'ocr_processed_at',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'component_id' => 'integer',
            'progress_weight' => 'decimal:2',
            'signatories' => 'array',
            'size' => 'integer',
            'ocr_processed_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
