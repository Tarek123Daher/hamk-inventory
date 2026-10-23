<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Component;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComponentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Component::with(['category', 'kits'])->where('is_active', true);
        $query->when($request->filled('q'), function ($query) use ($request) {
            $term = '%'.$request->string('q')->toString().'%';
            $query->where(fn ($search) => $search
                ->where('name', 'like', $term)
                ->orWhere('sku', 'like', $term)
                ->orWhere('model', 'like', $term)
                ->orWhere('manufacturer', 'like', $term)
                ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $term)));
        });
        $query->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')));
        $query->when($request->input('stock') === 'low', fn ($query) => $query->whereColumn('quantity', '<=', 'reorder_threshold'));
        $query->when($request->input('stock') === 'available', fn ($query) => $query->where('quantity', '>', 0));

        return view('components.index', [
            'components' => $query->orderBy('name')->paginate(12)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('components.form', ['component' => new Component, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['image_path'] = $request->file('image')?->store('components', 'public');
        unset($data['image']);
        $component = Component::create($data);
        $this->log($request, 'component.created', $component, 'Created component '.$component->name);

        return redirect()->route('components.show', $component)->with('success', 'Component created.');
    }

    public function show(Component $component): View
    {
        $component->load(['category', 'kits', 'stockMovements.user', 'borrowingItems.borrowing']);

        return view('components.show', compact('component'));
    }

    public function edit(Component $component): View
    {
        return view('components.form', ['component' => $component, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Component $component): RedirectResponse
    {
        $data = $this->validated($request, $component);
        unset($data['image']);
        if ($request->hasFile('image')) {
            if ($component->image_path) {
                Storage::disk('public')->delete($component->image_path);
            }
            $data['image_path'] = $request->file('image')->store('components', 'public');
        }
        $component->update($data);
        $this->log($request, 'component.updated', $component, 'Updated component '.$component->name);

        return redirect()->route('components.show', $component)->with('success', 'Component updated.');
    }

    public function destroy(Request $request, Component $component): RedirectResponse
    {
        $component->update(['is_active' => false]);
        $this->log($request, 'component.archived', $component, 'Archived component '.$component->name);

        return redirect()->route('components.index')->with('success', 'Component archived; transaction history was retained.');
    }

    private function validated(Request $request, ?Component $component = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('components', 'sku')->ignore($component?->id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'datasheet_url' => ['nullable', 'url', 'max:2048'],
            'reorder_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.name' => ['nullable', 'string', 'max:120'],
            'attributes.*.value' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    private function log(Request $request, string $action, Component $component, string $description): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => Component::class,
            'subject_id' => $component->id,
            'description' => $description,
        ]);
    }
}
