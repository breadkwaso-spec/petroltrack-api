<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetrolTrack — Staff Login</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 2rem; border-radius: 10px; width: 100%; max-width: 380px; box-shadow: 0 4px 14px rgba(0,0,0,.08); }
        label { display: block; font-weight: 600; margin-top: 1rem; margin-bottom: .35rem; }
        input { width: 100%; padding: .65rem; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; min-height: 44px; }
        button { width: 100%; margin-top: 1.25rem; padding: .75rem; background: #1d4ed8; color: #fff; border: 0; border-radius: 6px; font-weight: 600; cursor: pointer; min-height: 44px; }
        .alert-status { background: #ecfdf5; color: #065f46; padding: .75rem; border-radius: 6px; margin-bottom: 1rem; font-size: .9rem; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: .75rem; border-radius: 6px; margin-bottom: 1rem; font-size: .9rem; }
        .footer-link { text-align: center; margin-top: 1rem; font-size: .9rem; }
    </style>
</head>
<body>
<div class="card">
    <h2 style="margin-top:0;">PetrolTrack Login</h2>
    <p style="color:#6b7280; font-size:.9rem;">Sampaguita Victoria Gas Station</p>

    @if (session('status'))
        <div class="alert-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="employee_id">Employee ID</label>
        <input id="employee_id" type="text" name="employee_id" value="{{ old('employee_id') }}" placeholder="e.g. OWN-001 or ATT-001" required autofocus>

        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>

        <button type="submit">Sign In</button>
    </form>

    <div class="footer-link">
        New staff? <a href="{{ route('register') }}">Register an account</a>
    </div>
</div>
</body>
</html>