<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Borrowing;
use App\Models\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $components = Component::with('category')->where('is_active', true)->get();
        $activeBorrowings = Borrowing::with('items.component')
            ->whereIn('status', ['borrowed', 'partially_returned', 'overdue'])
            ->latest()
            ->get();

        $byCategory = $components->groupBy(fn (Component $component) => $component->category?->name ?? 'Uncategorised')
            ->map(fn ($items) => $items->sum('quantity'));

        $movementDays = DB::table('stock_movements')
            ->selectRaw('date(created_at) as day, SUM(quantity) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('dashboard', [
            'componentTypes' => $components->count(),
            'recordedUnits' => $components->sum('quantity'),
            'availableUnits' => $components->sum(fn (Component $component) => $component->available_quantity),
            'borrowedUnits' => $activeBorrowings->flatMap->items->sum(fn ($item) => $item->outstandingQuantity()),
            'lowStock' => $components->filter(fn (Component $component) => $component->quantity <= $component->reorder_threshold),
            'damagedUnits' => $components->sum('damaged_quantity'),
            'lostUnits' => $components->sum('lost_quantity'),
            'byCategory' => $byCategory,
            'movements' => $movementDays,
            'overdue' => $activeBorrowings->filter(fn (Borrowing $borrowing) => $borrowing->expected_return_at?->lt(today())),
            'activity' => ActivityLog::with('user')->latest()->limit(8)->get(),
        ]);
    }
}
