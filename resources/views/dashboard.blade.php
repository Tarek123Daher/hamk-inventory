@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <section class="hero">
        <div><p class="eyebrow">INVENTORY OVERVIEW</p><h1>Welcome, <span>{{ auth()->user()->name }}</span></h1><p>Manage your club's Arduino and robotics components in one place.</p></div>
        <img src="{{ asset('images/hamk-logo.png') }}" alt="HAMK AI and Robotics Club">
    </section>
    <div class="stats-grid">
        <article class="stat-card accent-red"><span>Component types</span><strong>{{ number_format($componentTypes) }}</strong><small>Distinct active records</small></article>
        <article class="stat-card accent-green"><span>Recorded units</span><strong>{{ number_format($recordedUnits) }}</strong><small>Database-recorded stock</small></article>
        <article class="stat-card accent-blue"><span>Available</span><strong>{{ number_format($availableUnits) }}</strong><small>After loans, damage and loss</small></article>
        <article class="stat-card accent-orange"><span>Borrowed</span><strong>{{ number_format($borrowedUnits) }}</strong><small>Outstanding units</small></article>
        <article class="stat-card accent-purple"><span>Low stock</span><strong>{{ number_format($lowStock->count()) }}</strong><small>At or below reorder level</small></article>
        <article class="stat-card accent-red"><span>Damaged / lost</span><strong>{{ number_format($damagedUnits) }} / {{ number_format($lostUnits) }}</strong><small>Tracked separately from available</small></article>
    </div>
    <div class="dashboard-grid">
        <section class="panel chart-panel">
            <div class="panel-heading"><div><p class="eyebrow">LAST 30 DAYS</p><h2>Stock movement</h2></div><a href="{{ route('inventory.index') }}">View movements →</a></div>
            @if($movements->isEmpty())
                <div class="empty-state">No stock movements recorded yet.</div>
            @else
                <div class="bars" role="img" aria-label="Daily movement quantity for the past month">
                    @foreach($movements as $movement)<div class="bar-wrap" title="{{ $movement->day }}: {{ $movement->total }}"><span class="bar" style="height: {{ min(100, max(8, abs($movement->total) * 5)) }}%"></span><small>{{ \Illuminate\Support\Carbon::parse($movement->day)->format('M j') }}</small></div>@endforeach
                </div>
            @endif
        </section>
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">RECORDED UNITS</p><h2>By category</h2></div><a href="{{ route('components.index') }}">Components →</a></div>
            @forelse($byCategory as $category => $quantity)
                <div class="category-row"><span>{{ $category }}</span><strong>{{ number_format($quantity) }}</strong><div class="meter"><i style="width: {{ $recordedUnits ? round($quantity / $recordedUnits * 100) : 0 }}%"></i></div></div>
            @empty<div class="empty-state">Add components to see category totals.</div>@endforelse
        </section>
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">ATTENTION</p><h2>Low stock</h2></div><a href="{{ route('components.index', ['stock' => 'low']) }}">View all →</a></div>
            @forelse($lowStock->take(5) as $component)
                <a class="list-row" href="{{ route('components.show', $component) }}"><span>{{ $component->name }}<small>{{ $component->sku ?: 'No SKU' }}</small></span><strong class="text-red">{{ $component->available_quantity }} / {{ $component->reorder_threshold }}</strong></a>
            @empty<div class="empty-state">No components at or below their reorder level.</div>@endforelse
        </section>
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">LATEST LOG</p><h2>Recent activity</h2></div></div>
            @forelse($activity as $log)
                <div class="list-row"><span>{{ $log->description }}<small>{{ $log->user?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</small></span></div>
            @empty<div class="empty-state">Activity will appear here when changes are recorded.</div>@endforelse
        </section>
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">DUE BACK</p><h2>Overdue borrowings</h2></div><a href="{{ route('borrowings.index') }}">View all →</a></div>
            @forelse($overdue->take(5) as $borrowing)
                <a class="list-row" href="{{ route('borrowings.index') }}"><span>{{ $borrowing->borrower_name }}<small>Due {{ $borrowing->expected_return_at?->format('M j, Y') }}</small></span><strong class="badge danger">Overdue</strong></a>
            @empty<div class="empty-state">No overdue borrowings.</div>@endforelse
        </section>
    </div>
@endsection
