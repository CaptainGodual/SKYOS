<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->where('is_admin', false)
            ->orderByRaw("CASE approval_status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END")
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.users.index', ['users' => $users]);
    }

    public function approve(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Administrator accounts do not require approval.');
        }

        $user->update(['approval_status' => User::APPROVAL_APPROVED]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "{$user->name}'s account has been approved.");
    }

    public function reject(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Administrator accounts cannot be rejected.');
        }

        $user->update(['approval_status' => User::APPROVAL_REJECTED]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "{$user->name}'s account has been rejected.");
    }
}
