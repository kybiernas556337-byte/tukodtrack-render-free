<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:150', 'email' => 'required|email|max:150|unique:users,email',
            'role' => ['required', Rule::exists('roles', 'key')], 'password' => 'required|string|min:12|max:100|confirmed',
        ]);
        $u = User::create(['name' => $d['name'], 'email' => $d['email'], 'role' => $d['role'],
            'password' => Hash::make($d['password']), 'must_change_password' => true, 'is_active' => $r->boolean('is_active')]);
        ActivityLog::record('Account added', $u->name.' · '.$u->roleLabel());

        return redirect()->route('users.index')->with('success', 'Saved successfully');
    }

    public function update(Request $r, User $user)
    {
        $d = $r->validate([
            'name' => 'required|string|max:150', 'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::exists('roles', 'key')], 'password' => 'nullable|string|min:12|max:100|confirmed',
        ]);
        $self = $user->id === auth()->id();   // you cannot demote or deactivate yourself
        $user->fill([
            'name' => $d['name'], 'email' => $d['email'],
            'role' => $self ? $user->role : $d['role'],
            'is_active' => $self ? true : $r->boolean('is_active'),
        ]);
        if (! empty($d['password'])) {
            abort_if($self, 422, 'Use Change password for your own account.');
            $user->password = Hash::make($d['password']);
            $user->must_change_password = true;
            $user->remember_token = Str::random(60);
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        $user->save();
        ActivityLog::record('Account updated', $user->name.' · '.$user->roleLabel());

        return redirect()->route('users.index')->with('success', 'Saved successfully');
    }
}
