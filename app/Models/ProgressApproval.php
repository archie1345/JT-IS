<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgressApproval extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'progress_report_id',
        'approved_by_client',
        'approved_by_internal',
    ];

    protected function casts(): array
    {
        return [
            'progress_report_id' => 'integer',
            'approved_by_client' => 'boolean',
            'approved_by_internal' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function progressReport(): BelongsTo
    {
        return $this->belongsTo(ProgressReport::class);
    }
}
