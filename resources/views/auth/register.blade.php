<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetrolTrack — Staff Registration</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 2rem; border-radius: 10px; width: 100%; max-width: 380px; box-shadow: 0 4px 14px rgba(0,0,0,.08); }
        label { display: block; font-weight: 600; margin-top: 1rem; margin-bottom: .35rem; }
        input { width: 100%; padding: .65rem; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; min-height: 44px; }
        button { width: 100%; margin-top: 1.25rem; padding: .75rem; background: #1d4ed8; color: #fff; border: 0; border-radius: 6px; font-weight: 600; cursor: pointer; min-height: 44px; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: .75rem; border-radius: 6px; margin-bottom: 1rem; font-size: .9rem; }
        .footer-link { text-align: center; margin-top: 1rem; font-size: .9rem; }
    </style>
</head>
<body>
<div class="card">
    <h2 style="margin-top:0;">Staff Registration</h2>
    <p style="color:#6b7280; font-size:.9rem;">Accounts start as <strong>Pending</strong> until approved by the Station Owner.</p>

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label for="full_name">Full Name</label>
        <input id="full_name" type="text" name="full_name" value="{{ old('full_name') }}" required autofocus>

        <label for="employee_id">Employee ID</label>
        <input id="employee_id" type="text" name="employee_id" value="{{ old('employee_id') }}" placeholder="e.g. ATT-002" required>

        <label for="password">Password (min 8 chars)</label>
        <input id="password" type="password" name="password" required>

        <label for="password_confirmation">Confirm Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required>

        <button type="submit">Submit Registration</button>
    </form>

    <div class="footer-link">
        Already approved? <a href="{{ route('login') }}">Back to Login</a>
    </div>
</div>
</body>
</html>