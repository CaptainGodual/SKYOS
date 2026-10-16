<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create([
            ...$request->validated(),
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        return redirect()
            ->route('login')
            ->with('status', 'Your account has been created and is pending administrator approval. You will be able to sign in once approved.');
    }
}
