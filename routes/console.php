<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Create the first account locally against Neon; credentials are entered privately.
Artisan::command('tukodtrack:owner', function () {
    if (App\Models\User::where('role', 'owner')->exists()) {
        $this->error('An owner already exists. Sign in with that account; this command will not change it.');
        return 1;
    }
    $name = trim((string) $this->ask('Owner name'));
    $email = strtolower(trim((string) $this->ask('Owner email')));
    $password = (string) $this->secret('Owner password (at least 12 characters)');
    $confirmation = (string) $this->secret('Confirm password');
    $validator = Illuminate\Support\Facades\Validator::make(
        compact('name', 'email', 'password'), [
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:12|max:100',
        ]
    );
    if ($validator->fails() || $password !== $confirmation) {
        foreach ($validator->errors()->all() as $message) $this->error($message);
        if ($password !== $confirmation) $this->error('Passwords do not match.');
        return 1;
    }
    App\Models\User::create([
        'name' => $name, 'email' => $email,
        'password' => Illuminate\Support\Facades\Hash::make($password),
        'role' => 'owner', 'is_active' => true,
    ]);
    $this->info('Owner created. Sign in with the email and password you entered.');
    return 0;
})->purpose('Create the first owner without overwriting an existing owner');
