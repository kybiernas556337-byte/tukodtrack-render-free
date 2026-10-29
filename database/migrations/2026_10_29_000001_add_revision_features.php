<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->string('key', 60)->primary();
            $t->string('name', 100)->unique();
            $t->string('access_profile', 20);
            $t->timestamps();
        });
        foreach (['owner' => 'Owner / Manager', 'cashier' => 'Cashier', 'stock' => 'Stock Personnel'] as $key => $name) {
            DB::table('roles')->insert(['key' => $key, 'name' => $name, 'access_profile' => $key, 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('must_change_password')->default(false);
            $t->foreign('role')->references('key')->on('roles');
        });
        Schema::table('deliveries', function (Blueprint $t) {
            $t->text('proof_data')->nullable();
            $t->string('proof_mime', 80)->nullable();
            $t->string('proof_name')->nullable();
            $t->string('remarks')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', fn (Blueprint $t) => $t->dropColumn(['proof_data', 'proof_mime', 'proof_name', 'remarks']));
        Schema::table('users', function (Blueprint $t) {
            $t->dropForeign(['role']);
            $t->dropColumn('must_change_password');
        });
        Schema::dropIfExists('roles');
    }
};
