<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\Component;
use App\Models\StockMovement;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index', [
            'components' => Component::with('category')->where('is_active', true)->orderBy('name')->get(),
            'overdue' => Borrowing::with('items.component')
                ->whereIn('status', ['borrowed', 'partially_returned'])
                ->whereDate('expected_return_at', '<', today())
                ->latest()
                ->get(),
            'movements' => StockMovement::with('component')->latest()->limit(10)->get(),
        ]);
    }

    public function export(): Response
    {
        $rows = Component::with('category')->where('is_active', true)->orderBy('name')->get();
        $callback = static function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['SKU', 'Name', 'Category', 'Manufacturer', 'Recorded units', 'Physically verified units', 'Verified at', 'Available units', 'Damaged', 'Lost', 'Reorder threshold']);
            foreach ($rows as $component) {
                fputcsv($output, [
                    self::spreadsheetSafe($component->sku),
                    self::spreadsheetSafe($component->name),
                    self::spreadsheetSafe($component->category?->name),
                    self::spreadsheetSafe($component->manufacturer),
                    $component->quantity,
                    $component->verified_quantity,
                    $component->verified_at?->toDateTimeString(),
                    $component->available_quantity,
                    $component->damaged_quantity,
                    $component->lost_quantity,
                    $component->reorder_threshold,
                ]);
            }
            fclose($output);
        };

        return response()->streamDownload($callback, 'hamk-inventory-'.today()->toDateString().'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function spreadsheetSafe(?string $value): ?string
    {
        return $value !== null && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
