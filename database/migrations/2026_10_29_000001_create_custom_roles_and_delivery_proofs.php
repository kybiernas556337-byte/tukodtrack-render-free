<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('custom_roles', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->string('name');
    $table->string('base_role');
    $table->timestamps();
  });
  Schema::table('deliveries', function (Blueprint $table) {
    $table->string('proof_received_name')->nullable();
    $table->text('proof_notes')->nullable();
    $table->timestamp('proof_confirmed_at')->nullable();
    $table->longText('proof_image_data')->nullable();
    $table->string('proof_image_mime', 40)->nullable();
  });
 }
 public function down(): void {
  Schema::table('deliveries', function (Blueprint $table) {
    $table->dropColumn(['proof_received_name','proof_notes','proof_confirmed_at','proof_image_data','proof_image_mime']);
  });
  Schema::dropIfExists('custom_roles');
 }
};
