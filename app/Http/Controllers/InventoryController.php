<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Component;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        return view('inventory.index', [
            'components' => Component::where('is_active', true)->orderBy('name')->get(),
            'movements' => StockMovement::with(['component', 'user'])->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'component_id' => ['required', 'exists:components,id'],
            'type' => ['required', 'in:received,issued,adjustment,damaged,lost,transferred'],
            'quantity' => ['required', 'integer', 'between:-1000000,1000000', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
        ]);

        if (in_array($data['type'], ['received', 'issued', 'damaged', 'lost', 'transferred'], true) && $data['quantity'] < 1) {
            throw ValidationException::withMessages(['quantity' => 'This movement requires a positive quantity.']);
        }

        DB::transaction(function () use ($request, $data) {
            $component = Component::query()->lockForUpdate()->findOrFail($data['component_id']);
            $quantity = $data['quantity'];
            $delta = match ($data['type']) {
                'received' => $quantity,
                'issued' => -$quantity,
                'adjustment' => $quantity,
                default => 0,
            };

            if (in_array($data['type'], ['issued', 'damaged', 'lost'], true) && $quantity > $component->available_quantity) {
                throw ValidationException::withMessages(['quantity' => 'The quantity exceeds currently available stock.']);
            }

            if ($data['type'] === 'adjustment' && $component->quantity + $delta < $component->damaged_quantity + $component->lost_quantity + $component->borrowed_quantity) {
                throw ValidationException::withMessages(['quantity' => 'Adjustment cannot reduce recorded stock below quantities already borrowed, damaged, or lost.']);
            }

            $component->quantity += $delta;
            if ($data['type'] === 'damaged') {
                $component->damaged_quantity += $quantity;
            }
            if ($data['type'] === 'lost') {
                $component->lost_quantity += $quantity;
            }
            $component->save();

            StockMovement::create($data + ['user_id' => $request->user()->id]);
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'stock.'.$data['type'],
                'subject_type' => Component::class,
                'subject_id' => $component->id,
                'description' => ucfirst($data['type']).' '.$quantity.' unit(s) of '.$component->name,
            ]);
        });

        return redirect()->route('inventory.index')->with('success', 'Stock movement recorded.');
    }
}
