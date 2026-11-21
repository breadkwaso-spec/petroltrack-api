<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'employee_id' => ['required', 'string', 'max:50', 'unique:users,employee_id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'employee_id' => strtoupper(trim($validated['employee_id'])),
            'password' => Hash::make($validated['password']),
            'role' => 'Attendant',
            'status' => 'Pending',
        ]);

        AuditLogger::log('ACCOUNT_REGISTERED', 'User', $user->id, $user->id);

        return redirect()->route('login')
            ->with('status', 'Account registered as Pending. Please wait for the Station Owner to approve your account.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'employee_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $employeeId = strtoupper(trim($credentials['employee_id']));
        $user = User::where('employee_id', $employeeId)->first();

        if (!$user) {
            AuditLogger::log('LOGIN_FAILED', 'User', $employeeId, null);
            return back()->withErrors(['employee_id' => 'Invalid Employee ID or password.'])->onlyInput('employee_id');
        }

        if ($user->locked_until && now()->lessThan($user->locked_until)) {
            $minutesLeft = now()->diffInMinutes($user->locked_until) + 1;
            AuditLogger::log('LOGIN_BLOCKED_LOCKED', 'User', $user->id, $user->id);
            return back()->withErrors([
                'employee_id' => "Account locked due to failed logins. Try again in {$minutesLeft} minute(s).",
            ])->onlyInput('employee_id');
        }

        if ($user->locked_until && now()->greaterThanOrEqualTo($user->locked_until)) {
            $user->update([
                'failed_attempts' => 0,
                'locked_until' => null,
                'status' => $user->status === 'Locked' ? 'Active' : $user->status,
            ]);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            $attempts = $user->failed_attempts + 1;
            $updates = ['failed_attempts' => $attempts];

            if ($attempts >= 5) {
                $updates['locked_until'] = now()->addMinutes(15);
                $updates['status'] = 'Locked';
                AuditLogger::log('ACCOUNT_LOCKED', 'User', $user->id, $user->id);
            } else {
                AuditLogger::log('LOGIN_FAILED', 'User', $user->id, $user->id);
            }

            $user->update($updates);

            $message = $attempts >= 5
                ? 'Too many failed attempts. Account locked for 15 minutes.'
                : 'Invalid Employee ID or password.';

            return back()->withErrors(['employee_id' => $message])->onlyInput('employee_id');
        }

        if ($user->status !== 'Active') {
            AuditLogger::log('LOGIN_BLOCKED_INACTIVE', 'User', $user->id, $user->id);
            return back()->withErrors([
                'employee_id' => "Your account is currently {$user->status}. Please contact the Station Owner.",
            ])->onlyInput('employee_id');
        }

        $user->update(['failed_attempts' => 0, 'locked_until' => null]);
        Auth::login($user);
        $request->session()->regenerate();

        AuditLogger::log('LOGIN_SUCCESS', 'User', $user->id, $user->id);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();
        AuditLogger::log('LOGOUT', 'User', $userId, $userId);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }
}