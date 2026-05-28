<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Rab;
use App\Models\Rap;
use App\Models\Client;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AiDocumentExtractionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dev/AiDocumentExtraction', [
            'clients' => Client::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Client $client): array => [
                    'id' => $client->id,
                    'name' => $client->name ?? 'Client #'.$client->id,
                ])
                ->values(),
            'projects' => Project::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'name' => $project->name ?? 'Project #'.$project->id,
                ])
                ->values(),
        ]);
    }

    public function ocr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,webp,bmp,tif,tiff,txt,csv', 'max:51200'],
        ]);

        $file = $validated['file'];

        if (in_array(strtolower($file->getClientOriginalExtension()), ['txt', 'csv'], true)) {
            return response()->json([
                'engine' => 'laravel-text',
                'text' => file_get_contents($file->getRealPath()) ?: '',
                'pages' => [],
            ]);
        }

        $ocrUrl = rtrim((string) config('services.ocr.url'), '/').'/ocr';

        try {
            $response = Http::connectTimeout(10)
                ->timeout((int) config('services.ocr.timeout'))
                ->attach(
                    'file',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName(),
                )
                ->post($ocrUrl);
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'OCR service did not respond before the timeout.',
                'detail' => sprintf(
                    'Timed out calling %s after %s seconds. For large RAB PDFs, restart the OCR service and keep OCR_PREFER_PDF_TEXT=1, OCR_FAST_MODE=1, and OCR_MAX_PAGES at a practical page count.',
                    $ocrUrl,
                    config('services.ocr.timeout'),
                ),
            ], 504);
        }

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->json() ?: $response->body();

            return response()->json([
                'message' => 'OCR service failed.',
                'detail' => $detail,
            ], $response->status() >= 500 ? 503 : $response->status());
        }

        return response()->json($response->json());
    }

    public function storeBudgetItems(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'auto_create_project' => ['nullable', 'boolean'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'kind' => ['required', 'string', 'in:rab,rap'],
            'items' => ['nullable', 'array'],
            'items.*.category' => ['nullable', 'string', 'max:150'],
            'items.*.sub_category' => ['nullable', 'string', 'max:150'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_price' => ['nullable', 'numeric', 'min:0'],
            'project_updates' => ['nullable', 'array'],
            'project_updates.name' => ['nullable', 'string', 'max:200'],
            'project_updates.contract_number' => ['nullable', 'string', 'max:100'],
            'project_updates.contract_value' => ['nullable', 'numeric', 'min:0'],
            'project_updates.location' => ['nullable', 'string'],
        ]);

        $result = DB::transaction(function () use ($validated): array {
            $projectUpdates = collect($validated['project_updates'] ?? [])
                ->filter(fn ($value): bool => $value !== null && $value !== '')
                ->only(['name', 'contract_number', 'contract_value', 'location'])
                ->all();
            $items = $validated['items'] ?? [];
            $projectCreated = false;

            if (($validated['project_id'] ?? null) !== null) {
                $project = Project::query()->findOrFail($validated['project_id']);
            } elseif ($validated['auto_create_project'] ?? false) {
                $project = Project::query()->create([
                    'client_id' => $validated['client_id'] ?? null,
                    'name' => $projectUpdates['name'] ?? 'Untitled extracted project',
                    'contract_number' => $projectUpdates['contract_number'] ?? null,
                    'contract_value' => $projectUpdates['contract_value'] ?? 0,
                    'location' => $projectUpdates['location'] ?? null,
                    'status' => 'planning',
                ]);
                $projectCreated = true;
            } else {
                throw ValidationException::withMessages([
                    'project_id' => 'Select a project or enable auto-create project.',
                ]);
            }

            $record = null;
            $created = 0;

            if ($items !== []) {
                $record = $validated['kind'] === 'rab'
                    ? Rab::query()->firstOrCreate(['project_id' => $project->id])
                    : Rap::query()->firstOrCreate(['project_id' => $project->id]);

                foreach ($items as $item) {
                    $record->items()->create([
                        'category' => $item['category'] ?? null,
                        'sub_category' => $item['sub_category'] ?? null,
                        'description' => $item['description'],
                        'unit' => $item['unit'] ?? null,
                        'quantity' => $item['quantity'] ?? null,
                        'unit_price' => $item['unit_price'] ?? null,
                        'total_price' => $item['total_price'] ?? null,
                    ]);

                    $created++;
                }

                $record->update([
                    'total_budget' => $record->items()->sum('total_price'),
                ]);
            }

            if ($projectUpdates !== []) {
                $project->update($projectUpdates);
            }

            return [
                'id' => $record?->id,
                'project_id' => $project->id,
                'project_name' => $project->name,
                'items_created' => $created,
                'total_budget' => (float) ($record?->total_budget ?? 0),
                'project_created' => $projectCreated,
                'project_updated' => $projectUpdates !== [],
            ];
        });

        return response()->json([
            'message' => $result['items_created'] > 0
                ? strtoupper($validated['kind']).' items saved.'
                : 'Project details saved.',
            ...$result,
        ]);
    }
}
