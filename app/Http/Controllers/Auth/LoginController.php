<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function showLoginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = trim($credentials['username']);
        $password = $credentials['password'];

        $user = User::where('username', $username)->first();

        if (!$user) {
            return back()->withErrors([
                'username' => 'المستخدم غير موجود.',
            ])->onlyInput('username');
        }

        if ($user->is_deleted) {
            return back()->withErrors([
                'username' => 'هذا المستخدم محذوف ولا يمكنه تسجيل الدخول.',
            ])->onlyInput('username');
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'username' => 'هذا المستخدم غير فعال حاليًا.',
            ])->onlyInput('username');
        }

        if ($user->is_locked) {
            return back()->withErrors([
                'username' => 'هذا المستخدم مقفل، يرجى مراجعة الإدارة.',
            ])->onlyInput('username');
        }

        if (empty($user->password)) {
            return back()->withErrors([
                'username' => 'هذا المستخدم لا يملك كلمة مرور صالحة.',
            ])->onlyInput('username');
        }

        if (!Hash::check($password, $user->password)) {
            return back()->withErrors([
                'password' => 'كلمة المرور غير صحيحة.',
            ])->onlyInput('username');
        }

        Auth::login($user, false);

        $user->update([
            'last_login_at' => now(),
        ]);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}