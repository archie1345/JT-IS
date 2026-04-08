<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

abstract class TableCrudController extends Controller
{
    protected string $table;

    public function index(Request $request): Response|JsonResponse
    {
        $records = $this->query()
            ->orderByDesc('id')
            ->paginate(15);

        $view = $this->inertiaView();

        if ($view !== null) {
            return Inertia::render($view, [
                'data' => $records,
            ]);
        }

        return response()->json($records);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->storeRules());
        $now = now();

        if ($this->hasTimestamps()) {
            $validated['created_at'] = $validated['created_at'] ?? $now;
            $validated['updated_at'] = $now;
        }

        $id = DB::table($this->table)->insertGetId($validated);

        return response()->json(
            $this->query()->where('id', $id)->first(),
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->query()->where('id', $id)->first();

        abort_if(! $record, 404);

        return response()->json($record);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate($this->updateRules($id));
        $query = $this->query()->where('id', $id);

        abort_if(! $query->exists(), 404);

        if ($this->hasTimestamps()) {
            $validated['updated_at'] = now();
        }

        $query->update($validated);

        return response()->json($this->query()->where('id', $id)->first());
    }

    public function destroy(int $id): JsonResponse
    {
        $query = $this->query()->where('id', $id);

        abort_if(! $query->exists(), 404);

        if ($this->hasSoftDeletes()) {
            $query->update([
                'deleted_at' => now(),
                ...($this->hasTimestamps() ? ['updated_at' => now()] : []),
            ]);
        } else {
            $query->delete();
        }

        return response()->json([
            'message' => 'Deleted successfully.',
        ]);
    }

    abstract protected function storeRules(): array;

    abstract protected function updateRules(int $id): array;

    protected function inertiaView(): ?string
    {
        return match ($this->table) {
            'clients' => 'Clients',
            'projects' => 'Projects',
            'raps', 'rabs', 'rap_items', 'rab_items' => 'RabRap',
            default => null,
        };
    }

    protected function hasTimestamps(): bool
    {
        return in_array($this->table, [
            'clients',
            'projects',
            'tenders',
            'rabs',
            'raps',
            'progress_reports',
            'invoices',
            'fund_requests',
            'users',
        ], true);
    }

    protected function hasSoftDeletes(): bool
    {
        return Schema::hasColumn($this->table, 'deleted_at');
    }

    protected function query()
    {
        $query = DB::table($this->table);

        if ($this->hasSoftDeletes()) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }
}
