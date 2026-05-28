<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
        'latitude',    
        'longitude',   
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
            ->where('approved_by_client', true)
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

    /**
     * @return list<array{type: string, level: string, message: string}>
     */
    public function mvpWarnings(): array
    {
        $warnings = [];
        $rapTotal = $this->rapTotal();
        $realizedCost = $this->realizedCostTotal();

        if ($rapTotal > 0 && $realizedCost > $rapTotal) {
            $warnings[] = [
                'type' => 'budget',
                'level' => 'critical',
                'message' => 'Budget Critical: realized cost is above RAP.',
            ];
        } elseif ($rapTotal > 0 && $realizedCost >= ($rapTotal * 0.9)) {
            $warnings[] = [
                'type' => 'budget',
                'level' => 'warning',
                'message' => 'Budget Warning: realized cost has reached at least 90% of RAP.',
            ];
        }

        if ($this->hasOverdueInvoice()) {
            $warnings[] = [
                'type' => 'payment',
                'level' => 'warning',
                'message' => 'Payment Warning: project has overdue invoice.',
            ];
        }

        $approvedProgress = $this->latestApprovedProgressPercent() ?? 0;
        if ($this->end_date && Carbon::parse($this->end_date)->isPast() && $approvedProgress < 100) {
            $warnings[] = [
                'type' => 'progress',
                'level' => 'critical',
                'message' => 'Progress Critical: project end date has passed without 100% approved progress.',
            ];
        }

        return $warnings;
    }

    public function mvpStatus(): string
    {
        $warnings = $this->mvpWarnings();

        if (collect($warnings)->contains(fn (array $warning): bool => $warning['level'] === 'critical')) {
            return 'Critical';
        }

        if ($warnings !== []) {
            return 'Warning';
        }

        return 'On Track';
    }
}
