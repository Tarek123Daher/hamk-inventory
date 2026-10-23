<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090d13"><title>Sign in · HAMK Inventory</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="login-page">
    <main class="login-card">
        <img src="{{ asset('images/hamk-logo.png') }}" alt="HAMK AI and Robotics Club" class="login-logo">
        <p class="eyebrow">AI & ROBOTICS CLUB</p><h1>HAMK Inventory</h1>
        <p class="muted">Sign in to manage the club's inventory.</p>
        @if($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('login.store') }}" class="stack-form">
            @csrf
            <label>Email address<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
            <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
            <label class="check-label"><input type="checkbox" name="remember" value="1"> Remember me</label>
            <button class="button primary full-width" type="submit">Sign in</button>
        </form>
        <p class="muted small">No account yet? Ask an administrator to create one.</p>
    </main>
</body>
</html>
