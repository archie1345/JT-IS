<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TableCrudController extends Controller
{
    protected string $table;

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 15), 100));

        return response()->json(
            $this->baseQuery()
                ->orderByDesc('id')
                ->paginate($perPage)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->storeRules());

        $id = DB::table($this->table)->insertGetId(
            $this->transformPayload($validated, true)
        );

        return response()->json(
            $this->baseQuery()->where('id', $id)->first(),
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->baseQuery()->where('id', $id)->first();

        if (! $record) {
            abort(404);
        }

        return response()->json($record);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $query = $this->baseQuery()->where('id', $id);

        if (! $query->exists()) {
            abort(404);
        }

        $validated = $request->validate($this->updateRules($id));
        $payload = $this->transformPayload($validated, false);

        if ($payload !== []) {
            $query->update($payload);
        }

        return response()->json(
            $this->baseQuery()->where('id', $id)->first()
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $query = $this->baseQuery()->where('id', $id);

        if (! $query->exists()) {
            abort(404);
        }

        if ($this->usesSoftDeletes()) {
            $payload = ['deleted_at' => now()];

            if ($this->usesTimestamps()) {
                $payload['updated_at'] = now();
            }

            $query->update($payload);
        } else {
            $query->delete();
        }

        return response()->json([
            'message' => 'Deleted successfully.',
        ]);
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Validation\Rules\Unique>>
     */
    abstract protected function storeRules(): array;

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Validation\Rules\Unique>>
     */
    abstract protected function updateRules(int $id): array;

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    protected function transformPayload(array $validated, bool $creating): array
    {
        if ($this->usesTimestamps()) {
            $now = now();

            if ($creating) {
                $validated['created_at'] ??= $now;
            }

            $validated['updated_at'] = $now;
        }

        return $validated;
    }

    protected function usesTimestamps(): bool
    {
        return true;
    }

    protected function usesSoftDeletes(): bool
    {
        return Schema::hasColumn($this->table, 'deleted_at');
    }

    protected function baseQuery()
    {
        $query = DB::table($this->table);

        if ($this->usesSoftDeletes()) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }
}
