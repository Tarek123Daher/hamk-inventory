@extends('layouts.app')
@section('title', 'CSV Import')
@section('content')
<div class="page-heading"><div><p class="eyebrow">DATA INTAKE</p><h1>Review inventory CSV</h1><p class="muted">Import reviewed worksheet data without treating recorded quantities as physical verification.</p></div><a class="button secondary" href="{{ asset('inventory-import-template.csv') }}" download>Download CSV header template</a></div>
@if(session('import_summary'))
    @php($summary = session('import_summary'))
    <section class="panel import-summary"><h2>Import summary</h2><div class="detail-stats"><div><small>Inserted</small><strong>{{ $summary['inserted'] }}</strong></div><div><small>Updated</small><strong>{{ $summary['updated'] }}</strong></div><div><small>Skipped</small><strong>{{ $summary['skipped'] }}</strong></div><div><small>Errors</small><strong>{{ $summary['errors'] }}</strong></div></div>
    @if($summary['error_details'])<details class="import-errors"><summary>Show row errors</summary><ul>@foreach($summary['error_details'] as $error)<li>{{ $error }}</li>@endforeach</ul></details>@endif
    </section>
@endif
<section class="panel form-panel">
    <h2>Upload for review</h2>
    <div class="callout">Excel workbooks are not imported directly. Export each relevant sheet as CSV, map its real columns to the headers shown below, and review all rows before committing. Required: <code>sku</code>, <code>name</code>, <code>recorded_quantity</code>. A stable SKU is required to prevent duplicate component records.</div>
    <form method="post" action="{{ route('imports.preview') }}" enctype="multipart/form-data" class="stack-form">@csrf
        <label>CSV file (maximum 2 MB, 500 data rows)<input type="file" name="file" accept=".csv,text/csv" required></label>
        <button class="button primary">Preview rows</button>
    </form>
    <h3>Supported optional headers</h3>
    <p class="muted small"><code>category</code>, <code>manufacturer</code>, <code>model</code>, <code>physical_quantity</code>, <code>verification_notes</code>, <code>reorder_threshold</code>, <code>description</code>, <code>kit_code</code>, <code>kit_name</code>, <code>kit_quantity</code>, <code>kit_notes</code>.</p>
    <p class="muted small">Existing SKU values are updated rather than duplicated. Re-importing the exact same file is blocked; changed files are reviewed again and stock deltas are recorded in movement history. Conflicting or invalid rows are skipped with an explicit error.</p>
</section>
<section class="panel table-panel"><div class="panel-heading"><h2>Recent imports</h2></div><div class="table-scroll"><table><thead><tr><th>Imported</th><th>Filename</th><th>Inserted</th><th>Updated</th><th>Skipped</th><th>Errors</th></tr></thead><tbody>@forelse($imports as $import)<tr><td>{{ $import->created_at->format('M j, Y H:i') }}</td><td>{{ $import->filename }}</td><td>{{ $import->inserted }}</td><td>{{ $import->updated }}</td><td>{{ $import->skipped }}</td><td>{{ $import->errors }}</td></tr>@empty<tr><td colspan="6" class="empty-state">No CSV imports have been processed.</td></tr>@endforelse</tbody></table></div></section>
@endsection
