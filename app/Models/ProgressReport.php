<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgressReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'document_number',
        'document_type',
        'progress_percent',
        'period_start',
        'period_end',
        'report_date',
        'description',
        'approved_by_internal',
    ];

    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'progress_percent' => 'float',
            'period_start' => 'date',
            'period_end' => 'date',
            'report_date' => 'date',
            'approved_by_internal' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approval(): HasOne
    {
        return $this->hasOne(ProgressApproval::class);
    }
}
