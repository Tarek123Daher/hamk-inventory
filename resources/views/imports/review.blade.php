@extends('layouts.app')
@section('title', 'Review import')
@section('content')
<div class="page-heading"><div><p class="eyebrow">PREVIEW · NO CHANGES SAVED</p><h1>Review {{ $preview['filename'] }}</h1><p class="muted">Only rows marked Insert or Update will be applied. Warnings preserve separate verified physical-count values.</p></div><a class="button ghost" href="{{ route('imports.index') }}">Cancel</a></div>
<section class="panel table-panel"><div class="table-scroll"><table><thead><tr><th>CSV row</th><th>Action</th><th>SKU</th><th>Component</th><th>Recorded</th><th>Physical</th><th>Kit</th><th>Review notes</th></tr></thead><tbody>
@foreach($preview['rows'] as $row)
<tr><td>{{ $row['_line'] }}</td><td><span class="badge {{ $row['_action'] === 'error' ? 'danger' : (in_array($row['_action'], ['insert','update'], true) ? 'good' : 'warning') }}">{{ ucfirst($row['_action']) }}</span></td>
<td>{{ $row['sku'] ?? '—' }}</td><td>{{ $row['name'] ?? '—' }}</td><td>{{ $row['recorded_quantity'] ?? '—' }}</td><td>{{ ($row['physical_quantity'] ?? '') !== '' ? $row['physical_quantity'] : 'Not supplied' }}</td>
<td>{{ ! empty($row['kit_code']) ? $row['kit_code'].' · '.$row['kit_name'] : 'Outside kit / not supplied' }}</td><td>{{ $row['_reason'] ?? $row['_warning'] ?? '—' }}</td></tr>
@endforeach
</tbody></table></div></section>
<form method="post" action="{{ route('imports.commit') }}" class="form-actions" data-confirm="Import the rows marked Insert or Update? This will write stock movements and an import audit record.">@csrf<a class="button ghost" href="{{ route('imports.index') }}">Cancel</a><button class="button primary">Import reviewed rows</button></form>
@endsection
