<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Signup as a short, two-step wizard: name+email, then password — rather
 * than four fields on one page. Step 1 checks 'unique:users,email' too
 * (not just the final store()) so a taken address is caught right next to
 * the field that's wrong, not on the password screen. store() itself is
 * unchanged and re-validates everything, since it's the one place a user
 * actually gets created.
 */
class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.register', [
            'name' => $request->query('name'),
            'email' => $request->query('email'),
        ]);
    }

    public function createStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        return redirect()->route('register.credentials', $data);
    }

    public function credentials(Request $request): View
    {
        return view('auth.register-credentials', [
            'name' => $request->query('name'),
            'email' => $request->query('email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            // Hashed by the model's cast, never stored in the clear.
            'password' => $data['password'],
            'status' => User::STATUS_ACTIVE,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
