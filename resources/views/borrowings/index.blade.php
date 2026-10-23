@extends('layouts.app')
@section('title', 'Borrowings')
@section('content')
<div class="page-heading"><div><p class="eyebrow">LOANS & RETURNS</p><h1>Borrowing and returns</h1></div>@if(in_array(auth()->user()->role, ['administrator','inventory_manager','coach'], true))<a class="button primary" href="{{ route('borrowings.create') }}">＋ New borrowing</a>@endif</div>
<form class="filter-bar" method="get"><input name="q" value="{{ request('q') }}" placeholder="Search borrower name…"><button class="button secondary">Search</button></form>
<section class="panel table-panel"><div class="table-scroll"><table><thead><tr><th>Borrower</th><th>Items</th><th>Borrowed</th><th>Due</th><th>Status</th><th>Return items</th></tr></thead><tbody>
@forelse($borrowings as $borrowing)
<tr><td>{{ $borrowing->borrower_name }}<small class="table-sub">{{ $borrowing->borrower_email }}</small></td>
<td>@foreach($borrowing->items as $item)<div>{{ $item->component?->name ?? $item->kit?->name ?? 'Archived component' }} × {{ $item->quantity }}</div>@endforeach</td>
<td>{{ $borrowing->borrowed_at->format('M j, Y') }}</td><td>{{ $borrowing->expected_return_at?->format('M j, Y') ?? '—' }}</td>
<td><span class="badge {{ $borrowing->status === 'returned' ? 'good' : ($borrowing->expected_return_at?->lt(today()) ? 'danger' : 'warning') }}">{{ $borrowing->expected_return_at?->lt(today()) && $borrowing->status !== 'returned' ? 'Overdue' : str_replace('_', ' ', ucfirst($borrowing->status)) }}</span></td>
<td>@if(in_array(auth()->user()->role, ['administrator','inventory_manager','coach'], true))@foreach($borrowing->items as $item)@if($item->outstandingQuantity() > 0)<form class="inline-return" method="post" action="{{ route('borrowings.return', [$borrowing, $item]) }}">@csrf<input aria-label="Return quantity" type="number" name="quantity" min="1" max="{{ $item->outstandingQuantity() }}" value="{{ $item->outstandingQuantity() }}"><button class="button secondary small-button">Return {{ $item->component?->name ?? 'item' }}</button></form>@endif @endforeach @else—@endif</td></tr>
@empty<tr><td colspan="6" class="empty-state">No borrowing records found.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $borrowings->links() }}</div></section>
@endsection
