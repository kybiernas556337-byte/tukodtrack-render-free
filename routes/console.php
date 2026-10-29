<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Create the first account locally against Neon; credentials are entered privately.
Artisan::command('tukodtrack:owner', function () {
    if (User::where('role', 'owner')->exists()) {
        $this->error('An owner already exists. Sign in with that account; this command will not change it.');

        return 1;
    }
    $name = trim((string) $this->ask('Owner name'));
    $email = strtolower(trim((string) $this->ask('Owner email')));
    $password = (string) $this->secret('Owner password (at least 12 characters)');
    $confirmation = (string) $this->secret('Confirm password');
    $validator = Validator::make(
        compact('name', 'email', 'password'), [
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:12|max:100',
        ]
    );
    if ($validator->fails() || $password !== $confirmation) {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }
        if ($password !== $confirmation) {
            $this->error('Passwords do not match.');
        }

        return 1;
    }
    User::create([
        'name' => $name, 'email' => $email,
        'password' => Hash::make($password),
        'role' => 'owner', 'is_active' => true,
    ]);
    $this->info('Owner created. Sign in with the email and password you entered.');

    return 0;
})->purpose('Create the first owner without overwriting an existing owner');

// Recovery requires trusted shell access; credentials are never command arguments.
Artisan::command('tukodtrack:reset-password', function () {
    $email = strtolower(trim((string) $this->ask('Account email')));
    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error('Account not found.');

        return 1;
    }
    $password = (string) $this->secret('New temporary password (at least 12 characters)');
    $confirmation = (string) $this->secret('Confirm password');
    if (strlen($password) < 12 || strlen($password) > 100 || $password !== $confirmation) {
        $this->error('Use matching passwords between 12 and 100 characters.');

        return 1;
    }
    DB::transaction(function () use ($user, $password) {
        $user->update(['password' => Hash::make($password),
            'must_change_password' => true, 'remember_token' => Str::random(60)]);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        ActivityLog::record('Password recovered via console', 'Account #'.$user->id);
    });
    $this->info('Password reset. The account must choose a new password at sign-in.');

    return 0;
})->purpose('Reset an existing account password using trusted shell access');
