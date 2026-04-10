<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

abstract class TableCrudController extends Controller
{
    // 1. GANTI DARI $table MENJADI $model
    protected string $model;

    public function index(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        // Gunakan Eloquent, otomatis handle SoftDeletes & Timestamps
        $records = $this->model::orderByDesc('id')->paginate(15);

        $view = $this->inertiaView();

        if ($view !== null) {
            return Inertia::render($view, [
                'data' => $records,
            ]);
        }

        return response()->json($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->storeRules());

        // Gunakan Eloquent Create
        $record = $this->model::create($validated);

        $this->afterStore($record, $request);

        // Jika inertia return redirect back
        return redirect()->back()->with('success', 'Data berhasil disimpan.');
    }

    public function show(int $id)
    {
        $record = $this->model::findOrFail($id);
        return response()->json($record);
    }

    public function update(Request $request, int $id)
    {
        $record = $this->model::findOrFail($id);
        $validated = $request->validate($this->updateRules($id));

        $record->update($validated);

        // Panggil fungsi pancingan (Hook) untuk Spatie
        $this->afterUpdate($record, $request);

        return redirect()->back()->with('success', 'Data berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        $record = $this->model::findOrFail($id);
        
        // Fitur SoftDeletes atau ForceDelete akan ditangani otomatis oleh Model
        $record->delete();

        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    // Fungsi kosong bawaan yang bisa di-override oleh Controller anak
    protected function afterStore($record, Request $request): void {}
    protected function afterUpdate($record, Request $request): void {}

    abstract protected function storeRules(): array;
    abstract protected function updateRules(int $id): array;

    protected function inertiaView(): ?string
    {
        // menggunakan nama Model agar lebih dinamis
        return match ($this->model) {
            \App\Models\Client::class => 'Clients',
            \App\Models\Project::class => 'Projects',
            \App\Models\Rab::class => 'Rabs',
            \App\Models\Rap::class => 'Raps',
            \App\Models\User::class => 'Admin/Users/Index', // Tambahkan View User
            default => null,
        };
    }
}