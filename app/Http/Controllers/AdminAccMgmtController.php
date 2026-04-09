<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminAccMgmtController extends Controller
{
    public function index(): Response
    {
        $this->ensureUserTypeEnum();

        $users = User::query()
            ->with('client:id,name')
            ->latest('id')
            ->get(['id', 'client_id', 'name', 'email', 'user_type', 'email_verified_at', 'created_at'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'clientId' => $user->client_id,
                'name' => $user->name,
                'email' => $user->email,
                'userType' => $user->user_type,
                'userTypeLabel' => match ($user->user_type) {
                    'admin' => 'Admin',
                    'employee' => 'Employee',
                    'client' => 'Client',
                    'jte' => 'JTE / Internal',
                    default => ucfirst((string) $user->user_type),
                },
                'clientName' => $user->client?->name,
                'verifiedAt' => optional($user->email_verified_at)->format('Y-m-d'),
                'createdAt' => optional($user->created_at)->format('Y-m-d'),
            ])
            ->all();

        $stats = [
            'total' => User::query()->count(),
            'admin' => User::query()->where('user_type', 'admin')->count(),
            'employee' => User::query()->where('user_type', 'employee')->count(),
            'jte' => User::query()->where('user_type', 'jte')->count(),
            'client' => User::query()->where('user_type', 'client')->count(),
        ];

        return Inertia::render('AdmnUsrMgmt', [
            'users' => $users,
            'clients' => Client::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Client $client): array => [
                    'id' => $client->id,
                    'name' => $client->name,
                ])
                ->all(),
            'stats' => $stats,
            'userTypes' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'employee', 'label' => 'Employee'],
                ['value' => 'jte', 'label' => 'JTE / Internal'],
                ['value' => 'client', 'label' => 'Client'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureUserTypeEnum();

        $data = $this->validatePayload($request);

        User::create([
            'client_id' => $data['user_type'] === 'client' ? $data['client_id'] : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'user_type' => $data['user_type'],
        ]);

        return to_route('admin.acc_mgmt')->with('success', 'Account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureUserTypeEnum();

        $data = $this->validatePayload($request, $user->id, true);

        $user->update([
            'client_id' => $data['user_type'] === 'client' ? $data['client_id'] : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'user_type' => $data['user_type'],
            ...(! empty($data['password']) ? ['password' => $data['password']] : []),
        ]);

        return to_route('admin.acc_mgmt')->with('success', 'Account updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureUserTypeEnum();

        $user->delete();

        return to_route('admin.acc_mgmt')->with('success', 'Account deleted successfully.');
    }

    protected function validatePayload(Request $request, ?int $userId = null, bool $updating = false): array
    {
        $rules = [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$updating ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'user_type' => ['required', Rule::in(['admin', 'employee', 'client', 'jte'])],
        ];

        $data = $request->validate($rules);

        if (($data['user_type'] ?? null) !== 'client') {
            $data['client_id'] = null;
        } else {
            $request->validate([
                'client_id' => ['required', 'integer', 'exists:clients,id'],
            ]);
        }

        return $data;
    }

    protected function ensureUserTypeEnum(): void
    {
        $columnType = DB::table('information_schema.columns')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'users')
            ->where('column_name', 'user_type')
            ->value('column_type');

        if (! is_string($columnType)) {
            return;
        }

        if (
            str_contains($columnType, "'admin'") &&
            str_contains($columnType, "'employee'")
        ) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY user_type ENUM('jte','employee','client','admin') NOT NULL DEFAULT 'jte'");
    }
}
