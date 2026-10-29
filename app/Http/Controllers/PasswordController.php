<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.password');
    }

    public function update(Request $r)
    {
        $d = $r->validate(['current_password' => 'required|current_password', 'password' => 'required|string|min:12|max:100|confirmed|different:current_password']);
        $r->user()->update(['password' => Hash::make($d['password']), 'must_change_password' => false, 'remember_token' => Str::random(60)]);
        DB::table('sessions')->where('user_id', $r->user()->id)->where('id', '!=', $r->session()->getId())->delete();
        $r->session()->regenerate();
        ActivityLog::record('Password changed');

        return redirect()->route('overview')->with('success', 'Password changed. Other sessions have been signed out.');
    }
}
