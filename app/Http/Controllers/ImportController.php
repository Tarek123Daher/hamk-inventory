<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Component;
use App\Models\InventoryImport;
use App\Models\Kit;
use App\Models\StockMovement;
use App\Services\InventoryCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View
    {
        return view('imports.index', ['imports' => InventoryImport::latest()->limit(20)->get()]);
    }

    public function preview(Request $request, InventoryCsvImporter $importer): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);
        $file = $data['file'];
        $hash = hash_file('sha256', $file->getRealPath());
        if ($hash === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded file could not be verified.']);
        }
        if (InventoryImport::where('sha256', $hash)->exists()) {
            return redirect()->route('imports.index')->with('success', 'This exact file has already been imported. No rows were changed.');
        }

        $rows = $importer->parse($file->getRealPath());
        $request->session()->put('inventory_import_preview', [
            'user_id' => $request->user()->id,
            'filename' => $file->getClientOriginalName(),
            'sha256' => $hash,
            'rows' => $rows,
        ]);

        return redirect()->route('imports.review');
    }

    public function review(Request $request): View|RedirectResponse
    {
        $preview = $request->session()->get('inventory_import_preview');
        if (! $preview || $preview['user_id'] !== $request->user()->id) {
            return redirect()->route('imports.index')->withErrors(['file' => 'Upload a CSV file to review it before importing.']);
        }

        return view('imports.review', compact('preview'));
    }

    public function commit(Request $request): RedirectResponse
    {
        $preview = $request->session()->get('inventory_import_preview');
        if (! $preview || $preview['user_id'] !== $request->user()->id) {
            return redirect()->route('imports.index')->withErrors(['file' => 'Your import review expired. Upload the CSV again.']);
        }
        if (InventoryImport::where('sha256', $preview['sha256'])->exists()) {
            $request->session()->forget('inventory_import_preview');

            return redirect()->route('imports.index')->with('success', 'This exact file has already been imported. No rows were changed.');
        }

        $import = InventoryImport::create([
            'user_id' => $request->user()->id,
            'filename' => $preview['filename'],
            'sha256' => $preview['sha256'],
        ]);
        $summary = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0, 'error_details' => []];

        foreach ($preview['rows'] as $row) {
            if (! in_array($row['_action'], ['insert', 'update'], true)) {
                $summary[$row['_action'] === 'skip' ? 'skipped' : 'errors']++;
                if ($row['_action'] === 'error') {
                    $summary['error_details'][] = 'CSV row '.$row['_line'].': '.$row['_reason'];
                }

                continue;
            }

            try {
                $action = DB::transaction(function () use ($request, $row, $import) {
                    $component = Component::where('sku', $row['sku'])->lockForUpdate()->first();
                    $wasNew = $component === null;
                    if ($wasNew) {
                        $component = new Component;
                        $component->sku = $row['sku'];
                    }

                    $categoryId = null;
                    if ($row['category'] !== '') {
                        $categoryId = Category::firstOrCreate(['name' => $row['category']])->id;
                    }

                    $targetQuantity = (int) $row['recorded_quantity'];
                    $delta = $targetQuantity - (int) ($component->quantity ?? 0);
                    if (! $wasNew && $targetQuantity < $component->damaged_quantity + $component->lost_quantity + $component->borrowed_quantity) {
                        throw ValidationException::withMessages([
                            'quantity' => 'Recorded quantity cannot be reduced below borrowed, damaged, and lost units.',
                        ]);
                    }

                    $component->fill([
                        'name' => $row['name'],
                        'category_id' => in_array('category', $row['_columns'], true) ? $categoryId : $component->category_id,
                        'manufacturer' => in_array('manufacturer', $row['_columns'], true) ? ($row['manufacturer'] ?: null) : $component->manufacturer,
                        'model' => in_array('model', $row['_columns'], true) ? ($row['model'] ?: null) : $component->model,
                        'description' => in_array('description', $row['_columns'], true) ? ($row['description'] ?: null) : $component->description,
                        'quantity' => $targetQuantity,
                        'reorder_threshold' => in_array('reorder_threshold', $row['_columns'], true) && $row['reorder_threshold'] !== ''
                            ? (int) $row['reorder_threshold']
                            : ($wasNew ? 0 : $component->reorder_threshold),
                        'is_active' => true,
                    ]);
                    if ($row['physical_quantity'] !== '') {
                        $component->verified_quantity = (int) $row['physical_quantity'];
                        $component->verified_at = now();
                        $component->verification_notes = $row['verification_notes'] ?: null;
                    }
                    $component->save();

                    if ($delta !== 0) {
                        StockMovement::create([
                            'component_id' => $component->id,
                            'user_id' => $request->user()->id,
                            'type' => 'adjustment',
                            'quantity' => $delta,
                            'source' => 'CSV import',
                            'reason' => 'Recorded quantity imported in batch #'.$import->id,
                        ]);
                    }

                    if ($row['kit_code'] !== '') {
                        $kit = Kit::firstOrCreate(
                            ['code' => $row['kit_code']],
                            ['name' => $row['kit_name']]
                        );
                        if ($kit->name !== $row['kit_name']) {
                            throw ValidationException::withMessages(['kit' => 'Kit code exists with a different name.']);
                        }
                        $kit->components()->syncWithoutDetaching([
                            $component->id => [
                                'quantity' => (int) $row['kit_quantity'],
                                'notes' => $row['kit_notes'] ?: null,
                            ],
                        ]);
                    }

                    return $wasNew ? 'inserted' : 'updated';
                });
                $summary[$action]++;
            } catch (ValidationException $exception) {
                $summary['errors']++;
                $messages = collect($exception->errors())->flatten()->implode(' ');
                $summary['error_details'][] = 'CSV row '.$row['_line'].': '.$messages;
            }
        }

        $import->update($summary);
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'inventory.csv_imported',
            'subject_type' => InventoryImport::class,
            'subject_id' => $import->id,
            'description' => "Imported {$summary['inserted']} new and {$summary['updated']} updated component row(s); {$summary['skipped']} skipped, {$summary['errors']} errors.",
        ]);
        $request->session()->forget('inventory_import_preview');

        return redirect()->route('imports.index')->with('import_summary', $summary);
    }
}
