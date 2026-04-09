<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'email',
        'password',
        'user_type',
        'employee_role',
        'dashboard_layout',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_id' => 'integer',
            'deleted_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'dashboard_layout' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $user): void {
            $user->syncSidebarRole();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function projectAssignments(): HasMany
    {
        return $this->hasMany(ProjectUser::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_users')
            ->withPivot('id', 'role', 'deleted_at');
    }

    public function fundRequests(): HasMany
    {
        return $this->hasMany(FundRequest::class, 'requested_by');
    }

    public function syncSidebarRole(): void
    {
        $roles = $this->sidebarRoleNames();

        if ($roles === []) {
            return;
        }

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->syncRoles($roles);
    }

    public function sidebarRoleName(): ?string
    {
        return $this->sidebarRoleNames()[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function sidebarRoleNames(): array
    {
        $baseRole = match ($this->user_type) {
            'admin', 'employee', 'client' => $this->user_type,
            'jte' => 'employee',
            default => null,
        };

        if ($baseRole === null) {
            return [];
        }

        $roles = [$baseRole];
        $employeeRole = $this->normalizedEmployeeRole();

        if ($baseRole === 'employee' && $employeeRole !== null) {
            $roles[] = $employeeRole;
        }

        return array_values(array_unique($roles));
    }

    public function normalizedEmployeeRole(): ?string
    {
        if (($this->user_type ?? null) !== 'employee') {
            return null;
        }

        $value = trim((string) ($this->employee_role ?? ''));

        if ($value === '') {
            return null;
        }

        return Str::slug($value);
    }
}
