<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'k.ybiernas.556337@umindanao.edu.ph'],
            ['name' => 'KATHYRINE CASSANDRA YBIERNAS', 'password' => Hash::make('ChangeMe123!'), 'role' => 'owner', 'is_active' => true]
        );
    }
}
