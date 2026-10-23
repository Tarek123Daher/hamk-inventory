<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\Component;
use App\Models\Kit;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BorrowingController extends Controller
{
    public function index(Request $request): View
    {
        $borrowings = Borrowing::with(['items.component', 'items.kit', 'user'])
            ->when($request->filled('q'), fn ($query) => $query->where('borrower_name', 'like', '%'.$request->string('q')->toString().'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('borrowings.index', compact('borrowings'));
    }

    public function create(): View
    {
        return view('borrowings.create', [
            'components' => Component::where('is_active', true)->orderBy('name')->get(),
            'kits' => Kit::with('components')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'borrower_name' => ['required', 'string', 'max:255'],
            'borrower_email' => ['nullable', 'email', 'max:255'],
            'component_id' => ['nullable', 'required_without:kit_id', 'exists:components,id'],
            'kit_id' => ['nullable', 'required_without:component_id', 'exists:kits,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'expected_return_at' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if (filled($data['component_id'] ?? null) === filled($data['kit_id'] ?? null)) {
            throw ValidationException::withMessages(['component_id' => 'Select exactly one component or kit.']);
        }

        DB::transaction(function () use ($request, $data) {
            $borrowing = Borrowing::create([
                'borrower_name' => $data['borrower_name'],
                'borrower_email' => $data['borrower_email'] ?? null,
                'user_id' => $request->user()->id,
                'borrowed_at' => today(),
                'expected_return_at' => $data['expected_return_at'] ?? null,
                'status' => 'borrowed',
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['component_id'])) {
                $component = Component::query()->lockForUpdate()->findOrFail($data['component_id']);
                $this->ensureAvailable($component, $data['quantity']);
                $borrowing->items()->create(['component_id' => $component->id, 'quantity' => $data['quantity']]);
                $this->movement($request, $component, -$data['quantity'], $borrowing, 'Borrowed by '.$borrowing->borrower_name);
            } else {
                $kit = Kit::with('components')->findOrFail($data['kit_id']);
                if ($kit->components->isEmpty()) {
                    throw ValidationException::withMessages(['kit_id' => 'This kit has no assigned components.']);
                }
                foreach ($kit->components as $kitComponent) {
                    $component = Component::query()->lockForUpdate()->findOrFail($kitComponent->id);
                    $quantity = (int) $kitComponent->pivot->quantity * $data['quantity'];
                    $this->ensureAvailable($component, $quantity);
                    $borrowing->items()->create([
                        'component_id' => $component->id,
                        'kit_id' => $kit->id,
                        'quantity' => $quantity,
                    ]);
                    $this->movement($request, $component, -$quantity, $borrowing, 'Kit '.$kit->name.' borrowed by '.$borrowing->borrower_name);
                }
            }

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'borrowing.created',
                'subject_type' => Borrowing::class,
                'subject_id' => $borrowing->id,
                'description' => 'Created borrowing for '.$borrowing->borrower_name,
            ]);
        });

        return redirect()->route('borrowings.index')->with('success', 'Borrowing recorded.');
    }

    public function return(Request $request, Borrowing $borrowing, BorrowingItem $item): RedirectResponse
    {
        abort_unless($item->borrowing_id === $borrowing->id, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100000']]);

        DB::transaction(function () use ($request, $borrowing, $item, $data) {
            $lockedItem = BorrowingItem::query()->lockForUpdate()->findOrFail($item->id);
            if ($data['quantity'] > $lockedItem->outstandingQuantity()) {
                throw ValidationException::withMessages(['quantity' => 'Return quantity exceeds the outstanding quantity.']);
            }

            $lockedItem->increment('returned_quantity', $data['quantity']);
            $component = Component::query()->lockForUpdate()->findOrFail($lockedItem->component_id);
            StockMovement::create([
                'component_id' => $component->id,
                'user_id' => $request->user()->id,
                'type' => 'returned',
                'quantity' => $data['quantity'],
                'reason' => 'Return from borrowing #'.$borrowing->id,
            ]);

            $borrowing->load('items');
            $outstanding = $borrowing->items()->get()->sum(fn (BorrowingItem $borrowingItem) => $borrowingItem->outstandingQuantity());
            $borrowing->update(['status' => $outstanding === 0 ? 'returned' : 'partially_returned', 'returned_at' => $outstanding === 0 ? today() : null]);
            ActivityLog::create([
                'user_id' => $request->user()->id,
                'action' => 'borrowing.returned',
                'subject_type' => Borrowing::class,
                'subject_id' => $borrowing->id,
                'description' => 'Returned '.$data['quantity'].' unit(s) from borrowing #'.$borrowing->id,
            ]);
        });

        return back()->with('success', 'Return recorded.');
    }

    private function ensureAvailable(Component $component, int $quantity): void
    {
        if (! $component->is_active || $quantity > $component->available_quantity) {
            throw ValidationException::withMessages(['quantity' => 'Not enough available stock for '.$component->name.'.']);
        }
    }

    private function movement(Request $request, Component $component, int $quantity, Borrowing $borrowing, string $reason): void
    {
        StockMovement::create([
            'component_id' => $component->id,
            'user_id' => $request->user()->id,
            'type' => 'borrowed',
            'quantity' => $quantity,
            'reason' => $reason.' (borrowing #'.$borrowing->id.')',
        ]);
    }
}
