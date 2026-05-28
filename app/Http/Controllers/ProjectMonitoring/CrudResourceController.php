<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

abstract class CrudResourceController extends Controller
{
    protected string $model = '';
    protected string $table = '';

    public function index(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        $paginator = $this->indexQuery($request)
            ->paginate($this->perPageFromRequest($request))
            ->withQueryString();

        $view = $this->inertiaView();

        if ($view !== null) {
            return Inertia::render($view, array_merge([
                'records' => $paginator->getCollection()
                    ->map(fn (Model $record): array => $this->transformRecord($record, $request))
                    ->values()
                    ->all(),
                'pagination' => $this->paginationMeta($paginator),
            ], $this->pageProps($request)));
        }

        return response()->json($paginator);
    }

    public function store(Request $request)
    {
        $validated = $this->prepareForStore($request->validate($this->storeRules()), $request);

        // Gunakan Eloquent Create
        $record = $this->modelClass()::create($validated);

        $this->afterStore($record, $request);

        // Jika inertia return redirect back
        return redirect()->back()->with('success', 'Data berhasil disimpan.');
    }

    public function show(int $id)
    {
        $record = $this->modelClass()::findOrFail($id);
        return response()->json($record);
    }

    public function update(Request $request, int $id)
    {
        $record = $this->modelClass()::findOrFail($id);
        $validated = $this->prepareForUpdate($request->validate($this->updateRules($id)), $request, $record);

        $record->update($validated);

        // Panggil fungsi pancingan (Hook) untuk Spatie
        $this->afterUpdate($record, $request);

        return redirect()->back()->with('success', 'Data berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        $record = $this->modelClass()::findOrFail($id);
        
        // Fitur SoftDeletes atau ForceDelete akan ditangani otomatis oleh Model
        $record->delete();
        $this->afterDestroy($record);

        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    // Fungsi kosong bawaan yang bisa di-override oleh Controller anak
    protected function afterStore($record, Request $request): void {}
    protected function afterUpdate($record, Request $request): void {}
    protected function afterDestroy($record): void {}
    protected function prepareForStore(array $validated, Request $request): array
    {
        return $validated;
    }

    protected function prepareForUpdate(array $validated, Request $request, Model $record): array
    {
        return $validated;
    }

    abstract protected function storeRules(): array;
    abstract protected function updateRules(int $id): array;

    protected function indexQuery(Request $request): Builder
    {
        return $this->modelClass()::query()->orderByDesc('id');
    }

    protected function transformRecord(Model $record, Request $request): array
    {
        return $record->toArray();
    }

    protected function pageProps(Request $request): array
    {
        return [];
    }

    protected function inertiaView(): ?string
    {
        return match ($this->modelClass()) {
            \App\Models\Client::class => 'clients/Index',
            \App\Models\Project::class => 'projects/Index',
            \App\Models\Rab::class => 'budget/rabs/Index',
            \App\Models\Rap::class => 'budget/raps/Index',
            \App\Models\User::class => 'Admin/Users/Index', // Tambahkan View User
            default => null,
        };
    }

    protected function modelClass(): string
    {
        if ($this->model !== '') {
            return $this->model;
        }

        if ($this->table === '') {
            throw new RuntimeException(sprintf('%s must define either $model or $table.', static::class));
        }

        return match ($this->table) {
            'clients' => \App\Models\Client::class,
            'projects' => \App\Models\Project::class,
            'project_users' => \App\Models\ProjectUser::class,
            'tenders' => \App\Models\Tender::class,
            'rabs' => \App\Models\Rab::class,
            'rab_items' => \App\Models\RabItem::class,
            'raps' => \App\Models\Rap::class,
            'rap_items' => \App\Models\RapItem::class,
            'progress_reports' => \App\Models\ProgressReport::class,
            'progress_approvals' => \App\Models\ProgressApproval::class,
            'project_costs' => \App\Models\ProjectCost::class,
            'invoices' => \App\Models\Invoice::class,
            'payments' => \App\Models\Payment::class,
            'fund_requests' => \App\Models\FundRequest::class,
            default => throw new RuntimeException(sprintf('No model mapping is configured for table [%s].', $this->table)),
        };
    }
}
