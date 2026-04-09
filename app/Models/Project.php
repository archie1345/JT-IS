<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects';
    protected $primaryKey = 'id';

    protected $fillable = [
        'client_id',
        'name',
        'contract_number',
        'contract_value',
        'start_date',
        'end_date',
        'location',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'client_id' => 'integer',
            'contract_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot('id', 'role', 'deleted_at');
    }

    public function rabs(): HasMany
    {
        return $this->hasMany(Rab::class);
    }

    public function raps(): HasMany
    {
        return $this->hasMany(Rap::class);
    }

    public function progressReports(): HasMany
    {
        return $this->hasMany(ProgressReport::class);
    }

    public function projectCosts(): HasMany
    {
        return $this->hasMany(ProjectCost::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function latestInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function fundRequests(): HasMany
    {
        return $this->hasMany(FundRequest::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }
}
