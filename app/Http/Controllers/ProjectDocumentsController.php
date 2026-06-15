<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Invoice;
use App\Models\ProjectCost;
use App\Models\ProjectDocument;
use App\Models\ProgressReport;
use App\Models\Rab;
use App\Models\Rap;
use App\Services\Ocr\OcrNotConfiguredException;
use App\Services\Ocr\OcrProviderException;
use App\Services\Ocr\OcrService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectDocumentsController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $allowedExtensions = implode(',', config('ocr.allowed_extensions', []));

        $data = $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'mimes:'.$allowedExtensions, 'max:'.config('ocr.max_file_size_kb')],
            'document_type' => ['nullable', 'string', 'max:80', Rule::in([
                'contract',
                'rab',
                'rap',
                'bamc',
                'invoice',
                'receipt',
                'other',
                'project',
                'project_cost',
                'progress_report',
                'pipeline',
            ])],
            'ocr_text' => ['nullable', 'string'],
            'ocr_engine' => ['nullable', 'string', 'max:100'],
            'component_type' => ['nullable', 'string', 'max:80', Rule::in([
                'project',
                'rab',
                'rap',
                'invoice',
                'project_cost',
                'progress_report',
                'pipeline',
            ])],
            'component_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $componentType = $data['component_type'] ?? 'project';
        $documentType = $data['document_type'] ?? $componentType;
        $componentId = $data['component_id'] ?? null;

        foreach ($data['documents'] as $file) {
            $directory = "projects/{$project->id}/{$componentType}";
            $storedPath = $file->store($directory, 'public');

            ProjectDocument::create([
                'project_id' => $project->id,
                'document_type' => $documentType,
                'component_type' => $componentType,
                'component_id' => $componentId,
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_name' => $file->getClientOriginalName(),
                'path' => $storedPath,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'ocr_text' => $data['ocr_text'] ?? null,
                'ocr_engine' => $data['ocr_engine'] ?? null,
                'ocr_processed_at' => filled($data['ocr_text'] ?? null) ? now() : null,
            ]);
        }

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function ocr(Request $request, OcrService $ocrService): JsonResponse
    {
        $allowedExtensions = implode(',', config('ocr.allowed_extensions', []));

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:'.$allowedExtensions, 'max:'.config('ocr.max_file_size_kb')],
        ]);

        try {
            return response()->json($ocrService->extract($validated['file']));
        } catch (OcrNotConfiguredException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'engine' => 'manual',
                'text' => '',
                'pages' => [],
            ], 503);
        } catch (OcrProviderException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'engine' => 'manual',
                'text' => '',
                'pages' => [],
            ], $exception->statusCode());
        }
    }

    public function ocrStored(ProjectDocument $projectDocument, OcrService $ocrService): JsonResponse
    {
        abort_unless($projectDocument->project()->exists(), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($projectDocument->path), 404);

        $file = new UploadedFile(
            $disk->path($projectDocument->path),
            $projectDocument->original_name,
            $projectDocument->mime_type,
            null,
            true,
        );

        try {
            $payload = $ocrService->extract($file);
        } catch (OcrNotConfiguredException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'engine' => 'manual',
                'text' => '',
                'pages' => [],
            ], 503);
        } catch (OcrProviderException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'engine' => 'manual',
                'text' => '',
                'pages' => [],
            ], $exception->statusCode());
        }

        $projectDocument->update([
            'ocr_text' => $payload['text'] ?? '',
            'ocr_engine' => $payload['engine'] ?? 'ocr',
            'ocr_processed_at' => now(),
        ]);

        return response()->json([
            ...$payload,
            'document' => self::serialize($projectDocument->refresh()),
        ]);
    }

    public function applyExtraction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'component_type' => ['required', 'string', Rule::in([
                'project',
                'rab',
                'rap',
                'invoice',
                'project_cost',
                'progress_report',
            ])],
            'component_id' => ['nullable', 'integer', 'min:1'],
            'project_updates' => ['nullable', 'array'],
            'project_updates.name' => ['nullable', 'string'],
            'project_updates.contract_number' => ['nullable', 'string'],
            'project_updates.contract_value' => ['nullable', 'numeric', 'min:0'],
            'project_updates.location' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.category' => ['nullable', 'string', 'max:150'],
            'items.*.sub_category' => ['nullable', 'string', 'max:150'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_price' => ['nullable', 'numeric', 'min:0'],
            'progress_percent' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $result = DB::transaction(function () use ($validated): array {
            $project = Project::query()->findOrFail($validated['project_id']);
            $projectUpdates = collect($validated['project_updates'] ?? [])
                ->filter(fn ($value): bool => $value !== null && $value !== '')
                ->only(['name', 'contract_number', 'contract_value', 'location'])
                ->map(function ($value, string $key) {
                    if (! is_string($value)) {
                        return $value;
                    }

                    return match ($key) {
                        'name' => substr(trim($value), 0, 200),
                        'contract_number' => substr(trim($value), 0, 100),
                        default => trim($value),
                    };
                })
                ->all();
            $items = $validated['items'] ?? [];
            $itemsCreated = 0;
            $recordId = $validated['component_id'] ?? null;

            if ($projectUpdates !== []) {
                $project->update($projectUpdates);
            }

            if ($validated['component_type'] === 'rab') {
                $record = $recordId
                    ? Rab::query()->where('project_id', $project->id)->findOrFail($recordId)
                    : Rab::query()->firstOrCreate(['project_id' => $project->id]);
                $recordId = $record->id;

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
                    $itemsCreated++;
                }

                $record->update(['total_budget' => $record->items()->sum('total_price')]);
            }

            if ($validated['component_type'] === 'rap') {
                $record = $recordId
                    ? Rap::query()->where('project_id', $project->id)->findOrFail($recordId)
                    : Rap::query()->firstOrCreate(['project_id' => $project->id]);
                $recordId = $record->id;

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
                    $itemsCreated++;
                }

                $record->update(['total_budget' => $record->items()->sum('total_price')]);
            }

            if ($validated['component_type'] === 'progress_report' && ($validated['progress_percent'] ?? null) !== null) {
                $record = $recordId
                    ? ProgressReport::query()->where('project_id', $project->id)->findOrFail($recordId)
                    : $project->progressReports()->create(['report_date' => now()->toDateString()]);
                $record->update(['progress_percent' => $validated['progress_percent']]);
                $recordId = $record->id;
            }

            if ($validated['component_type'] === 'invoice' && ($validated['amount'] ?? null) !== null) {
                $amount = (float) $validated['amount'];
                $contractValue = (float) ($project->contract_value ?? 0);
                $approvedProgress = $project->latestApprovedProgressPercent();

                if ($contractValue <= 0) {
                    throw ValidationException::withMessages([
                'amount' => 'Draft tagihan dari OCR belum bisa diterapkan karena nilai kontrak proyek masih kosong.',
                    ]);
                }

                if ($approvedProgress === null || $approvedProgress <= 0) {
                    throw ValidationException::withMessages([
                'amount' => 'Draft tagihan dari OCR belum bisa diterapkan karena proyek belum memiliki progress yang disetujui lengkap.',
                    ]);
                }

                $remainingBillable = ($contractValue * ($approvedProgress / 100)) - $project->invoiceTotal($recordId);

                if ($amount > $remainingBillable) {
                    throw ValidationException::withMessages([
                'amount' => 'Draft tagihan dari OCR melebihi sisa nilai yang boleh ditagihkan berdasarkan progress disetujui.',
                    ]);
                }

                $record = $recordId
                    ? Invoice::query()->where('project_id', $project->id)->findOrFail($recordId)
                    : $project->invoices()->create(['invoice_date' => now()->toDateString(), 'status' => 'pending']);
                $record->update(['amount' => $amount]);
                $recordId = $record->id;
            }

            if ($validated['component_type'] === 'project_cost' && ($validated['amount'] ?? null) !== null) {
                $record = $recordId
                    ? ProjectCost::query()->where('project_id', $project->id)->findOrFail($recordId)
                    : $project->projectCosts()->create(['date' => now()->toDateString()]);
                $record->update(['amount' => $validated['amount']]);
                $recordId = $record->id;
            }

            return [
                'project_id' => $project->id,
                'project_updated' => $projectUpdates !== [],
                'component_type' => $validated['component_type'],
                'component_id' => $recordId,
                'items_created' => $itemsCreated,
            ];
        });

        return response()->json([
            'message' => 'Draft hasil OCR berhasil diterapkan.',
            ...$result,
        ]);
    }

    public function show(ProjectDocument $projectDocument)
    {
        abort_unless($projectDocument->project()->exists(), 404);

        $disk = Storage::disk('public');

        return $disk->response(
            $projectDocument->path,
            $projectDocument->original_name,
        );
    }

    public function destroy(ProjectDocument $projectDocument): RedirectResponse
    {
        Storage::disk('public')->delete($projectDocument->path);
        $projectDocument->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    public static function serialize(ProjectDocument $document): array
    {
        return [
            'id' => $document->id,
            'projectId' => $document->project_id,
            'projectName' => $document->project?->name,
            'name' => $document->name,
            'originalName' => $document->original_name,
            'url' => route('projects.documents.show', $document),
            'mimeType' => $document->mime_type,
            'size' => $document->size,
            'documentType' => $document->document_type,
            'componentType' => $document->component_type,
            'componentId' => $document->component_id,
            'ocrText' => $document->ocr_text,
            'ocrEngine' => $document->ocr_engine,
            'ocrProcessedAt' => optional($document->ocr_processed_at)->format('Y-m-d H:i'),
            'ocrStatus' => $document->ocr_processed_at ? 'processed' : 'not_processed',
            'createdAt' => optional($document->created_at)->format('Y-m-d H:i'),
        ];
    }
}
