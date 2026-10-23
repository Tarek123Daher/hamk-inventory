@extends('layouts.app')
@section('title', $component->name)
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $component->sku ?: 'COMPONENT' }}</p><h1>{{ $component->name }}</h1><p class="muted">{{ $component->category?->name ?? 'Uncategorised' }} · {{ $component->manufacturer ?: 'Manufacturer not recorded' }}</p></div><div class="button-row"><a class="button ghost" href="{{ route('components.index') }}">← Library</a>@if(in_array(auth()->user()->role, ['administrator','inventory_manager','coach'], true))<a class="button primary" href="{{ route('components.edit', $component) }}">Edit</a>@endif</div></div>
<div class="detail-grid">
    <section class="panel detail-photo">@if($component->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($component->image_path) }}" alt="{{ $component->name }}">@else<div class="image-placeholder">▦<small>Image not supplied</small></div>@endif</section>
    <section class="panel">
        <p class="eyebrow">STOCK SUMMARY</p><div class="detail-stats"><div><small>Available</small><strong>{{ $component->available_quantity }}</strong></div><div><small>Recorded</small><strong>{{ $component->quantity }}</strong></div><div><small>Borrowed</small><strong>{{ $component->borrowed_quantity }}</strong></div><div><small>Damaged / lost</small><strong>{{ $component->damaged_quantity }} / {{ $component->lost_quantity }}</strong></div></div>
        <hr><h2>Overview</h2><p>{{ $component->description ?: 'No description recorded.' }}</p>
        <dl class="definition-list"><dt>Model</dt><dd>{{ $component->model ?: '—' }}</dd><dt>Reorder threshold</dt><dd>{{ $component->reorder_threshold }}</dd><dt>Physical count</dt><dd>{{ $component->verified_quantity === null ? 'Not verified' : $component->verified_quantity.' · '.$component->verified_at?->format('M j, Y') }}</dd><dt>Verification notes</dt><dd>{{ $component->verification_notes ?: '—' }}</dd><dt>Datasheet</dt><dd>@if($component->datasheet_url)<a href="{{ $component->datasheet_url }}" target="_blank" rel="noopener noreferrer">Open datasheet ↗</a>@else—@endif</dd><dt>Kits</dt><dd>{{ $component->kits->pluck('name')->join(', ') ?: 'Not assigned' }}</dd></dl>
    </section>
    <section class="panel span-two"><h2>Specifications</h2>@forelse($component->attributes ?? [] as $attribute)<div class="list-row"><span>{{ $attribute['name'] ?? 'Specification' }}</span><strong>{{ $attribute['value'] ?? '—' }}</strong></div>@empty<p class="muted">No technical specifications recorded.</p>@endforelse</section>
    <section class="panel"><h2>Stock history</h2>@forelse($component->stockMovements->take(10) as $movement)<div class="list-row"><span>{{ ucfirst($movement->type) }}: {{ $movement->quantity }}<small>{{ $movement->reason ?: 'No reason provided' }} · {{ $movement->created_at->format('M j, Y H:i') }}</small></span></div>@empty<p class="muted">No stock movements yet.</p>@endforelse</section>
    <section class="panel"><h2>Borrowing history</h2>@forelse($component->borrowingItems->take(10) as $item)<div class="list-row"><span>{{ $item->borrowing->borrower_name }}<small>{{ $item->quantity }} borrowed · {{ $item->returned_quantity }} returned · {{ $item->borrowing->borrowed_at->format('M j, Y') }}</small></span></div>@empty<p class="muted">No borrowing history yet.</p>@endforelse</section>
</div>
@if(auth()->user()->role === 'administrator')
<form method="post" action="{{ route('components.destroy', $component) }}" class="archive-form" data-confirm="Archive this component? Its transaction history will be retained.">@csrf @method('delete')<button class="button danger-button" type="submit">Archive component</button></form>
@endif
@endsection
