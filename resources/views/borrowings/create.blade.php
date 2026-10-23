@extends('layouts.app')
@section('title', 'New borrowing')
@section('content')
<div class="page-heading"><div><p class="eyebrow">BORROWING</p><h1>Record a borrowing</h1><p class="muted">Choose an individual component or borrow a complete kit.</p></div><a href="{{ route('borrowings.index') }}" class="button ghost">← Borrowing list</a></div>
<form class="panel form-panel" method="post" action="{{ route('borrowings.store') }}">@csrf
    <div class="form-grid">
        <label>Borrower name *<input name="borrower_name" value="{{ old('borrower_name') }}" required maxlength="255"></label>
        <label>Borrower email<input type="email" name="borrower_email" value="{{ old('borrower_email') }}"></label>
        <label>Component<select name="component_id" data-borrow-component><option value="">Select a component…</option>@foreach($components as $component)<option value="{{ $component->id }}" @selected(old('component_id') == $component->id)>{{ $component->name }} ({{ $component->available_quantity }} available)</option>@endforeach</select></label>
        <label>Kit<select name="kit_id" data-borrow-kit><option value="">Select a kit…</option>@foreach($kits as $kit)<option value="{{ $kit->id }}" @selected(old('kit_id') == $kit->id)>{{ $kit->name }} ({{ $kit->components->count() }} component types)</option>@endforeach</select></label>
        <label>Quantity (units or kit sets) *<input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required></label>
        <label>Expected return date<input type="date" name="expected_return_at" value="{{ old('expected_return_at') }}"></label>
        <label class="span-two">Notes<textarea name="notes" rows="3">{{ old('notes') }}</textarea></label>
    </div>
    <p class="form-hint">Select either a component or a kit, not both. For kit loans, quantities are calculated from the saved kit contents.</p>
    <div class="form-actions"><a class="button ghost" href="{{ route('borrowings.index') }}">Cancel</a><button class="button primary">Save borrowing</button></div>
</form>
@endsection
