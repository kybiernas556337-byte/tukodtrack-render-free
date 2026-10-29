<?php
namespace App\Http\Controllers;

use App\Models\{ActivityLog, User, CustomRole};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::orderBy('name')->get(), 'roles' => User::ROLES + CustomRole::orderBy('name')->pluck('name','slug')->all()]);
    }

    public function storeRole(Request $r)
    {
        $data = $r->validate([
            'role_name' => 'required|string|max:80|unique:custom_roles,name',
            'base_role' => ['required', Rule::in(['cashier','stock'])],
        ]);
        $slug = 'custom-' . \Illuminate\Support\Str::random(12);
        CustomRole::create(['slug' => $slug, 'name' => $data['role_name'], 'base_role' => $data['base_role']]);
        ActivityLog::record('Custom role added', $data['role_name'] . ' · template: ' . $data['base_role']);
        return back()->with('success', 'Custom role created. Assign it to an account.');
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:150', 'email' => 'required|email|max:150|unique:users,email',
            'role' => ['required', Rule::in(array_merge(array_keys(User::ROLES), CustomRole::pluck('slug')->all()))], 'password' => ['required', 'string', 'max:100', Password::min(8)->mixedCase()->symbols()],
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
            'role' => ['required', Rule::in(array_merge(array_keys(User::ROLES), CustomRole::pluck('slug')->all()))], 'password' => ['nullable', 'string', 'max:100', Password::min(8)->mixedCase()->symbols()],
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
