<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2362d9">
    <title>Sign in · {{ config('app.name') }}</title>
    <style>
        :root { --brand: #2362d9; --ink: #1b2436; --muted: #6b7280; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(1200px 600px at 50% -10%, #2f6be0 0%, #0f1c38 55%, #0a1225 100%);
            color: var(--ink); padding: 24px; }
        .card { width: 100%; max-width: 400px; background: #fff; border-radius: 18px;
            padding: 36px 32px; box-shadow: 0 30px 60px rgba(6, 12, 28, .45); }
        .mark { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center;
            background: var(--brand); color: #fff; font-weight: 800; font-size: 22px; margin-bottom: 20px; }
        h1 { font-size: 22px; margin: 0 0 6px; }
        p.sub { margin: 0 0 24px; color: var(--muted); font-size: 14px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        input[type=email], input[type=password] { width: 100%; padding: 11px 13px; font-size: 15px;
            border: 1px solid #d2d8e3; border-radius: 10px; outline: none; }
        input:focus { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(35, 98, 217, .16); }
        .field { margin-bottom: 18px; }
        .row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); margin-bottom: 22px; }
        button { width: 100%; padding: 12px; font-size: 15px; font-weight: 600; color: #fff;
            background: var(--brand); border: 0; border-radius: 10px; cursor: pointer; }
        button:hover { background: #1c4fb4; }
        .errors { background: #fdecec; color: #a4232b; border: 1px solid #f6c6c6;
            border-radius: 10px; padding: 10px 12px; font-size: 13px; margin-bottom: 18px; }
        .errors ul { margin: 0; padding-left: 16px; }
    </style>
</head>
<body>
    <form class="card" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mark">S</div>
        <h1>SKYOS Crew Center</h1>
        <p class="sub">Sign in to access dispatch, ACARS and your roster.</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>

        <label class="row">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>

        <button type="submit">Sign in</button>
    </form>
</body>
</html>
