@extends('layouts.app')
@section('title', 'Components')
@section('content')
<div class="page-heading"><div><p class="eyebrow">INVENTORY</p><h1>Components library</h1><p class="muted">Search and review recorded components and their availability.</p></div>
    @if(in_array(auth()->user()->role, ['administrator', 'inventory_manager', 'coach'], true))<a class="button primary" href="{{ route('components.create') }}">＋ Add component</a>@endif
</div>
<form class="filter-bar" method="get">
    <input name="q" value="{{ request('q') }}" placeholder="Name, SKU, model, manufacturer…">
    <select name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach</select>
    <select name="stock"><option value="">All stock statuses</option><option value="available" @selected(request('stock') === 'available')>Recorded stock &gt; 0</option><option value="low" @selected(request('stock') === 'low')>At/below reorder level</option></select>
    <button class="button secondary" type="submit">Filter</button>
    @if(request()->hasAny(['q', 'category', 'stock']))<a class="button ghost" href="{{ route('components.index') }}">Clear</a>@endif
</form>
<div class="component-grid">
    @forelse($components as $component)
    <article class="component-card">
        <a class="component-image" href="{{ route('components.show', $component) }}">
            @if($component->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($component->image_path) }}" alt="{{ $component->name }}">@else<span aria-label="No component image">▦<small>Image not supplied</small></span>@endif
        </a>
        <div class="component-info"><div class="between"><span class="eyebrow">{{ $component->sku ?: 'SKU NOT SET' }}</span><span class="badge {{ $component->available_quantity > $component->reorder_threshold ? 'good' : 'danger' }}">{{ $component->available_quantity > $component->reorder_threshold ? 'Available' : 'Low stock' }}</span></div>
            <h2><a href="{{ route('components.show', $component) }}">{{ $component->name }}</a></h2>
            <p class="muted">{{ $component->category?->name ?? 'Uncategorised' }}{{ $component->manufacturer ? ' · '.$component->manufacturer : '' }}</p>
            <div class="stock-line"><span>Available <strong>{{ $component->available_quantity }}</strong></span><span>Recorded <strong>{{ $component->quantity }}</strong></span></div>
            <div class="kit-tags">@forelse($component->kits as $kit)<span>{{ $kit->code }}</span>@empty<span>Not assigned to a kit</span>@endforelse</div>
        </div>
    </article>
    @empty<div class="panel empty-state full-width">No components match the current filters.</div>@endforelse
</div>
<div class="pagination">{{ $components->links() }}</div>
@endsection
