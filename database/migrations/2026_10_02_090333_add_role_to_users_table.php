<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        if (! Schema::hasColumn('users', 'name')) $table->string('name')->nullable();
        if (! Schema::hasColumn('users', 'email')) $table->string('email')->nullable()->unique();
        if (! Schema::hasColumn('users', 'is_active')) $table->boolean('is_active')->default(true);
        if (! Schema::hasColumn('users', 'updated_at')) $table->timestamp('updated_at')->nullable();
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $columns = array_values(array_filter(['name', 'email', 'is_active', 'updated_at'], fn ($column) => Schema::hasColumn('users', $column)));
        if ($columns) $table->dropColumn($columns);
    });
}
};
