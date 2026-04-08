<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

abstract class TableCrudController extends Controller
{
    protected string $model;
    protected string $inertiaView;

    public static function middleware(): array {
        return [];
    }

    public function index(Request $request)
    {
        // MENGGUNAKAN ELOQUENT, BUKAN DB::table()
        $records = $this->model::latest()->paginate(15);

        // KEMBALIKAN HALAMAN INERTIA VUE, BUKAN JSON
        return Inertia::render($this->inertiaView, [
            'data' => $records
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->storeRules());

        // MENGGUNAKAN ELOQUENT
        $record = $this->model::create($validated);

        // Beri "Hook" / fungsi pancingan agar anak class bisa melakukan sesuatu setelah data dibuat
        // (Misalnya: Menyematkan Role Spatie)
        $this->afterStore($record, $request);

        return redirect()->back()->with('success', 'Data berhasil ditambahkan.');
    }

    // Fungsi kosong yang bisa di-override oleh controller turunannya (seperti UsersController)
    protected function afterStore($record, Request $request): void
    {
        // Defaultnya kosong.
    }

    abstract protected function storeRules(): array;
    abstract protected function updateRules(int $id): array;
}