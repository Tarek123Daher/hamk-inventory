@extends('layouts.app')
@section('title', 'Kits')
@section('content')
<div class="page-heading"><div><p class="eyebrow">KIT MANAGEMENT</p><h1>Kits</h1><p class="muted">Manage kit definitions and component quantities. Unassigned components remain supported.</p></div></div>
@if(in_array(auth()->user()->role, ['administrator','inventory_manager','coach'], true))
<form class="panel form-panel" method="post" action="{{ route('kits.store') }}">@csrf<h2>Create kit</h2><div class="form-grid"><label>Kit code *<input name="code" required placeholder="KIT-01"></label><label>Kit name *<input name="name" required></label><label>Location<input name="location"></label><label>Description<textarea name="description" rows="2"></textarea></label></div><div class="form-actions"><button class="button primary">Create kit</button></div></form>
@endif
<div class="kit-grid">@forelse($kits as $kit)
<section class="panel kit-card"><div class="panel-heading"><div><p class="eyebrow">{{ $kit->code }}</p><h2>{{ $kit->name }}</h2></div><span class="badge">{{ $kit->components->count() }} component types</span></div>
    @if($kit->description)<p class="muted">{{ $kit->description }}</p>@endif
    @if($kit->location)<p class="muted">Location: {{ $kit->location }}</p>@endif
    <ul class="kit-components">@forelse($kit->components as $component)<li><a href="{{ route('components.show', $component) }}">{{ $component->name }}</a><span>× {{ $component->pivot->quantity }}</span>@if($component->pivot->notes)<small>{{ $component->pivot->notes }}</small>@endif</li>@empty<li class="muted">No components assigned.</li>@endforelse</ul>
    @if(in_array(auth()->user()->role, ['administrator','inventory_manager','coach'], true))
    <details class="kit-edit"><summary>Edit kit contents</summary><form method="post" action="{{ route('kits.components.sync', $kit) }}">@csrf @method('put')
        @foreach($components as $component)@php($assigned = $kit->components->firstWhere('id', $component->id))
        <label class="kit-edit-row"><span>{{ $component->name }}</span><input aria-label="Quantity for {{ $component->name }}" type="number" min="1" name="components[{{ $component->id }}][quantity]" value="{{ $assigned?->pivot->quantity }}" placeholder="Not assigned"></label>
        @endforeach
        <button class="button secondary">Save contents</button></form></details>
    @endif
</section>@empty<div class="panel empty-state">No kits have been created.</div>@endforelse</div>
@endsection
