<?php
namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('profile.password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->symbols()],
        ]);
        $user = $request->user();
        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        $request->session()->regenerate();
        ActivityLog::record('Password changed', 'Password changed by account owner');
        return redirect()->route('password.edit')->with('success', 'Your password has been updated.');
    }
}
