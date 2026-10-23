<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Component;
use App\Models\Kit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KitController extends Controller
{
    public function index(): View
    {
        return view('kits.index', [
            'kits' => Kit::with('components')->orderBy('name')->get(),
            'components' => Component::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80', 'unique:kits,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);
        $kit = Kit::create($data);
        $this->log($request, $kit, 'Created kit '.$kit->name);

        return redirect()->route('kits.index')->with('success', 'Kit created.');
    }

    public function syncComponents(Request $request, Kit $kit): RedirectResponse
    {
        $data = $request->validate([
            'components' => ['nullable', 'array'],
            'components.*.quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'components.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $kit, $data) {
            $contents = array_filter(
                $data['components'] ?? [],
                fn (array $component) => ! empty($component['quantity'])
            );
            $kit->components()->sync($contents);
            $this->log($request, $kit, 'Updated components in kit '.$kit->name);
        });

        return back()->with('success', 'Kit contents updated.');
    }

    private function log(Request $request, Kit $kit, string $description): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'kit.updated',
            'subject_type' => Kit::class,
            'subject_id' => $kit->id,
            'description' => $description,
        ]);
    }
}
