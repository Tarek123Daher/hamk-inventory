<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090d13">
    <title>@yield('title', 'Dashboard') · HAMK Inventory</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">
        <a class="brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/hamk-logo.png') }}" alt="HAMK AI and Robotics Club">
            <span><strong>HAMK Inventory</strong><small>AI & Robotics Club</small></span>
        </a>
        <nav>
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif><span>⌂</span> Dashboard</a>
            <a class="{{ request()->routeIs('components.*') ? 'active' : '' }}" href="{{ route('components.index') }}" @if(request()->routeIs('components.*')) aria-current="page" @endif><span>▦</span> Components</a>
            <a class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}" @if(request()->routeIs('inventory.*')) aria-current="page" @endif><span>⇄</span> Inventory</a>
            @if(in_array(auth()->user()->role, ['administrator', 'inventory_manager', 'coach'], true))
                <a class="{{ request()->routeIs('imports.*') ? 'active' : '' }}" href="{{ route('imports.index') }}" @if(request()->routeIs('imports.*')) aria-current="page" @endif><span>⇩</span> Import data</a>
            @endif
            <a class="{{ request()->routeIs('borrowings.*') ? 'active' : '' }}" href="{{ route('borrowings.index') }}" @if(request()->routeIs('borrowings.*')) aria-current="page" @endif><span>↗</span> Borrowing & Returns</a>
            <a class="{{ request()->routeIs('kits.*') ? 'active' : '' }}" href="{{ route('kits.index') }}" @if(request()->routeIs('kits.*')) aria-current="page" @endif><span>⬡</span> Kits</a>
            <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}" @if(request()->routeIs('reports.*')) aria-current="page" @endif><span>▥</span> Reports</a>
            @if(auth()->user()->role === 'administrator')
                <a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" @if(request()->routeIs('users.*')) aria-current="page" @endif><span>♙</span> Users</a>
            @endif
            <a class="{{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}" @if(request()->routeIs('settings.*')) aria-current="page" @endif><span>⚙</span> Settings</a>
        </nav>
        <div class="sidebar-foot">BUILD · LEARN · INNOVATE</div>
    </aside>
    <button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Close navigation"></button>
    <main class="main-area">
        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar" data-sidebar-toggle>☰</button>
            <div class="top-title"><strong>HAMK Inventory</strong><small>Arduino & Robotics Club Management System</small></div>
            <form class="global-search" action="{{ route('components.index') }}" method="get">
                <button class="search-toggle" type="button" aria-label="Open search" aria-expanded="false" aria-controls="global-search-input" data-search-toggle><span aria-hidden="true">⌕</span></button><input id="global-search-input" name="q" value="{{ request('q') }}" placeholder="Search components, categories, or codes…" aria-label="Search components, categories, or codes">
            </form>
            <div class="account">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span><strong>{{ auth()->user()->name }}</strong><small>{{ str_replace('_', ' ', ucfirst(auth()->user()->role)) }}</small></span>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="link-button" type="submit">Log out</button></form>
            </div>
        </header>
        <section class="page-content" id="main-content" tabindex="-1">
            @if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())
                <div class="notice error" role="alert"><strong>Please review the form:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </section>
    </main>
</div>
<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
