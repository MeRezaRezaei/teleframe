<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tl_users_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_users_users_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_users_users')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_users_users_users__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_users_users_users')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9ab1062583b9b61990f1');
        });
        Schema::create('tl_users_users_users_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_users_users')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_users_users_users_slice__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_users_users_users_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f2d914dcd8bf411210fd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_users_users_users_slice__users');
        Schema::dropIfExists('tl_users_users_users_slice');
        Schema::dropIfExists('tl_users_users_users__users');
        Schema::dropIfExists('tl_users_users_users');
        Schema::dropIfExists('tl_users_users');
    }
};
