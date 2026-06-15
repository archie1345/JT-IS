<?php

namespace App\Models;

use App\Services\ProjectStatusService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'name',
        'contract_number',
        'contract_value',
        'start_date',
        'end_date',
        'location',
        'latitude',
        'longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'contract_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'deleted_at' => 'datetime',
        ];
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

    public function rabTotal(): float
    {
        return (float) $this->rabs()
            ->withSum('items as items_total', 'total_price')
            ->get()
            ->sum(fn (Rab $rab): float => (float) ($rab->items_total ?? 0));
    }

    public function rapTotal(): float
    {
        return (float) $this->raps()
            ->withSum('items as items_total', 'total_price')
            ->get()
            ->sum(fn (Rap $rap): float => (float) ($rap->items_total ?? 0));
    }

    public function realizedCostTotal(): float
    {
        return (float) $this->projectCosts()->sum('amount');
    }

    public function invoiceTotal(?int $exceptInvoiceId = null): float
    {
        return (float) $this->invoices()
            ->when($exceptInvoiceId, fn ($query) => $query->whereKeyNot($exceptInvoiceId))
            ->sum('amount');
    }

    public function latestProgressReport(): ?ProgressReport
    {
        return $this->progressReports()
            ->latest('report_date')
            ->latest('id')
            ->first();
    }

    public function latestApprovedProgressReport(): ?ProgressReport
    {
        return $this->progressReports()
            ->where('approved_by_internal', true)
            ->latest('report_date')
            ->latest('id')
            ->first();
    }

    public function latestApprovedProgressPercent(): ?float
    {
        $report = $this->latestApprovedProgressReport();

        return $report ? (float) $report->progress_percent : null;
    }

    public function hasOverdueInvoice(): bool
    {
        return $this->invoices()
            ->where(function ($query): void {
                $query->where('status', 'overdue')
                    ->orWhere(function ($query): void {
                        $query->where('status', '!=', 'paid')
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', now()->toDateString());
                    });
            })
            ->exists();
    }

    public function projectHealthWarnings(): array
    {
        return app(ProjectStatusService::class)->warnings($this);
    }

    public function projectHealthStatus(): string
    {
        return app(ProjectStatusService::class)->status($this);
    }
}
