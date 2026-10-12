<?php
namespace App\Http\Controllers;

use App\Models\{ActivityLog, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'role' => ['required', Rule::in(array_keys(User::ROLES))], 'password' => 'required|string|min:8|max:100',
        ]);
        $u = User::create(['name' => $d['name'], 'email' => $d['email'], 'role' => $d['role'],
            'password' => Hash::make($d['password']), 'is_active' => $r->boolean('is_active')]);
        ActivityLog::record('Account added', $u->name . ' · ' . $u->roleLabel());
        return redirect()->route('users.index')->with('success', 'Saved successfully');
    }

    public function update(Request $r, User $user)
    {
        $d = $r->validate([
            'name' => 'required|string|max:150', 'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))], 'password' => 'nullable|string|min:8|max:100',
        ]);
        $self = $user->id === auth()->id();   // you cannot demote or deactivate yourself
        $user->fill([
            'name' => $d['name'], 'email' => $d['email'],
            'role' => $self ? $user->role : $d['role'],
            'is_active' => $self ? true : $r->boolean('is_active'),
        ]);
        if (!empty($d['password'])) $user->password = Hash::make($d['password']);
        $user->save();
        ActivityLog::record('Account updated', $user->name . ' · ' . $user->roleLabel());
        return redirect()->route('users.index')->with('success', 'Saved successfully');
    }
}
