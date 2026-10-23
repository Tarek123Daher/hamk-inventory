<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090d13">
    <title>Student Inventory · HAMK Inventory</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="portal-shell">
    <header class="portal-header">
        <a class="portal-brand" href="{{ route('student-portal') }}">
            <img src="{{ asset('images/hamk-logo.png') }}" alt="HAMK AI and Robotics Club">
            <span><strong>HAMK Inventory</strong><small>Student component catalogue</small></span>
        </a>
        <div class="portal-account">
            <span>{{ auth()->user()->name }}</span>
            <a class="button ghost" href="{{ route('dashboard') }}">Dashboard</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="button secondary" type="submit">Log out</button></form>
        </div>
    </header>

    <section class="portal-intro">
        <div><p class="eyebrow">HAMK AI & ROBOTICS CLUB</p><h1>Student inventory portal</h1><p>Browse component information, current availability, and kit contents. Inventory is read-only in this portal.</p></div>
        <span class="portal-readonly">READ ONLY</span>
    </section>

    <section class="portal-section" aria-labelledby="components-heading">
        <div class="portal-section-heading">
            <div><p class="eyebrow">COMPONENTS</p><h2 id="components-heading">Browse the catalogue</h2></div>
            <form class="portal-search" method="get" action="{{ route('student-portal') }}">
                <label class="visually-hidden" for="portal-search">Search components</label>
                <input id="portal-search" name="q" value="{{ $term }}" placeholder="Search name, SKU, model…">
                <button class="button primary" type="submit">Search</button>
            </form>
        </div>

        <div class="portal-component-grid">
            @forelse($components as $component)
                <article class="portal-component">
                    <div class="portal-component-image">
                        @if($component->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($component->image_path) }}" alt="{{ $component->name }}">
                        @else
                            <span aria-label="No component image">▦<small>Image not supplied</small></span>
                        @endif
                    </div>
                    <div class="portal-component-body">
                        <div class="between"><span class="eyebrow">{{ $component->sku ?: 'SKU NOT SET' }}</span><span class="badge {{ $component->available_quantity > 0 ? 'good' : 'danger' }}">{{ $component->available_quantity > 0 ? 'Available' : 'Unavailable' }}</span></div>
                        <h3>{{ $component->name }}</h3>
                        <p class="muted">{{ $component->category?->name ?? 'Uncategorised' }}</p>
                        <dl class="portal-facts">
                            <dt>Available stock</dt><dd>{{ $component->available_quantity }}</dd>
                            @if($component->manufacturer)<dt>Manufacturer</dt><dd>{{ $component->manufacturer }}</dd>@endif
                            @if($component->model)<dt>Model</dt><dd>{{ $component->model }}</dd>@endif
                        </dl>
                        @if($component->description)<p class="portal-description">{{ $component->description }}</p>@endif
                        @if($component->attributes)
                            <details class="portal-specs"><summary>Specifications</summary>
                                <dl class="portal-facts">@foreach($component->attributes as $attribute)<dt>{{ $attribute['name'] ?? 'Specification' }}</dt><dd>{{ $attribute['value'] ?? '—' }}</dd>@endforeach</dl>
                            </details>
                        @else
                            <p class="portal-no-specs">No specifications recorded.</p>
                        @endif
                        @if($component->datasheet_url)<a class="portal-datasheet" href="{{ $component->datasheet_url }}" target="_blank" rel="noopener noreferrer">Open datasheet ↗</a>@endif
                        <p class="portal-kit-assignment">Kit assignment: {{ $component->kits->pluck('code')->join(', ') ?: 'Not assigned to a kit' }}</p>
                    </div>
                </article>
            @empty
                <div class="panel empty-state portal-empty">No components match the search.</div>
            @endforelse
        </div>
        <div class="pagination">{{ $components->links() }}</div>
    </section>

    <section class="portal-section" aria-labelledby="kits-heading">
        <div class="portal-section-heading"><div><p class="eyebrow">KIT CONTENTS</p><h2 id="kits-heading">Club kits</h2></div></div>
        <div class="portal-kit-grid">
            @forelse($kits as $kit)
                <article class="panel portal-kit">
                    <div class="between"><div><p class="eyebrow">{{ $kit->code }}</p><h3>{{ $kit->name }}</h3></div><span class="badge">{{ $kit->components->count() }} component types</span></div>
                    @if($kit->description)<p class="muted">{{ $kit->description }}</p>@endif
                    <ul>
                        @forelse($kit->components as $component)
                            <li><span>{{ $component->name }}</span><strong>× {{ $component->pivot->quantity }}</strong></li>
                        @empty
                            <li class="muted">No active components are assigned to this kit.</li>
                        @endforelse
                    </ul>
                </article>
            @empty
                <div class="panel empty-state">No kits are recorded.</div>
            @endforelse
        </div>
    </section>
    <footer class="portal-footer">HAMK Inventory · Component availability reflects recorded stock and current loans.</footer>
</main>
</body>
</html>
