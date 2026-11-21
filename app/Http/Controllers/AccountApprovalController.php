<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AccountApprovalController extends Controller
{
    public function index()
    {
        $pendingUsers = User::where('status', 'Pending')->orderBy('created_at')->get();
        $staffUsers = User::where('status', '!=', 'Pending')->orderBy('full_name')->get();

        return view('owner.accounts', compact('pendingUsers', 'staffUsers'));
    }

    public function approve(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:Attendant,Owner'],
        ]);

        $user->update([
            'role' => $validated['role'],
            'status' => 'Active',
            'failed_attempts' => 0,
            'locked_until' => null,
            'approved_by' => auth()->id(),
        ]);

        AuditLogger::log('ACCOUNT_APPROVED', 'User', $user->id);

        return back()->with('status', "Approved {$user->full_name} ({$user->employee_id}) as {$validated['role']}.");
    }

    public function reject(User $user)
    {
        $info = "{$user->full_name} ({$user->employee_id})";
        $userId = $user->id;

        AuditLogger::log('ACCOUNT_REJECTED', 'User', $userId);
        $user->delete();

        return back()->with('status', "Rejected and removed pending registration for {$info}.");
    }
}