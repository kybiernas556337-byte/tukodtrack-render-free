<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show() { return view('auth.login'); }

    public function login(Request $r)
    {
        $c = $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (Auth::attempt(['email' => $c['email'], 'password' => $c['password'], 'is_active' => true], $r->boolean('remember'))) {
            $r->session()->regenerate();
            return redirect()->intended(route('overview'));
        }
        return back()->withErrors(['email' => 'These credentials do not match an active account.'])->onlyInput('email');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
