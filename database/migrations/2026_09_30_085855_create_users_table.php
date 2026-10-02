<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
    $table->id('id_users');
    $table->string('username', 100)->unique();
    $table->string('password', 255);
    $table->string('role', 20)->default('kasir');
    $table->string('status', 20)->default('aktif');
    $table->timestamp('created_at')->useCurrent();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
