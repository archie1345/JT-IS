<?php

namespace App\Providers;

use App\Support\AccessControl;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {

    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->grantAdminFullAccess();
        $this->syncAccessControl();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function syncAccessControl(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        AccessControl::sync();
    }

    protected function grantAdminFullAccess(): void
    {
        Gate::before(function ($user): ?bool {
            if (($user->user_type ?? null) === 'admin' || $user->hasRole('admin')) {
                return true;
            }

            return null;
        });
    }
}
