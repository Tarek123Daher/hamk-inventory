@extends('layouts.app')
@section('title', 'Inventory')
@section('content')
<div class="page-heading"><div><p class="eyebrow">STOCK CONTROL</p><h1>Inventory management</h1><p class="muted">Record receipts, issues, adjustments, damage, loss, and transfers.</p></div></div>
@if(in_array(auth()->user()->role, ['administrator','inventory_manager','coach'], true))
<form class="panel form-panel" method="post" action="{{ route('inventory.store') }}">@csrf<h2>Record a stock movement</h2><div class="form-grid">
    <label>Component *<select name="component_id" required><option value="">Select component…</option>@foreach($components as $component)<option value="{{ $component->id }}">{{ $component->name }} — {{ $component->available_quantity }} available</option>@endforeach</select></label>
    <label>Movement type *<select name="type" required><option value="received">Received</option><option value="issued">Issued</option><option value="adjustment">Adjustment (+/-)</option><option value="damaged">Damaged</option><option value="lost">Lost</option><option value="transferred">Transfer (no stock change)</option></select></label>
    <label>Quantity *<input type="number" name="quantity" required min="-1000000" max="1000000" value="{{ old('quantity') }}"></label>
    <label>Source<input name="source" maxlength="255" placeholder="Location or kit"></label><label>Destination<input name="destination" maxlength="255" placeholder="Location or kit"></label>
    <label class="span-two">Reason / notes<textarea name="reason" rows="2"></textarea></label>
</div><div class="form-actions"><button class="button primary">Save movement</button></div></form>
@endif
<section class="panel table-panel"><div class="panel-heading"><h2>Movement history</h2><span class="muted">{{ $movements->total() }} records</span></div><div class="table-scroll"><table><thead><tr><th>Date</th><th>Component</th><th>Type</th><th>Quantity</th><th>Reason</th><th>Recorded by</th></tr></thead><tbody>
@forelse($movements as $movement)<tr><td>{{ $movement->created_at->format('M j, Y H:i') }}</td><td><a href="{{ route('components.show', $movement->component) }}">{{ $movement->component->name }}</a></td><td>{{ ucfirst($movement->type) }}</td><td>{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td><td>{{ $movement->reason ?: '—' }}</td><td>{{ $movement->user?->name ?? '—' }}</td></tr>@empty<tr><td colspan="6" class="empty-state">No movement records yet.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $movements->links() }}</div></section>
@endsection
