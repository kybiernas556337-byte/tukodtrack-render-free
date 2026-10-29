<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:100|unique:roles,name', 'access_profile' => 'required|in:cashier,stock']);
        $key = 'custom_'.Str::random(20);
        Role::create(['key' => $key] + $d);
        ActivityLog::record('Role created', $d['name'].' · '.$d['access_profile']);

        return back()->with('success', 'Role added. You can now assign it to an account.');
    }
}
