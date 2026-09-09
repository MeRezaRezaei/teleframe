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
        Schema::create('tl_users_user_full', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4618dab8eff2116adadaa806');
            $table->index('account_id', 'ix_86649f40f7772deda4c5c985');
        });
        Schema::create('tl_users_user_full_user_full', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_users_user_full')->cascadeOnDelete();
            $table->uuid('full_user');
            $table->index('full_user', 'ix_cfded6d34d5161ff5bb7e54d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8ed1b416bbff5c5b4a910040');
        });
        Schema::create('tl_users_user_full_user_full__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_users_user_full_user_full')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c5a28a0d556e08661c73');
            $table->index('account_id', 'ix_a61bcd8b8815c43c1a923747');
        });
        Schema::create('tl_users_user_full_user_full__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_users_user_full_user_full')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6eb1a00193078e95cfce');
            $table->index('account_id', 'ix_c9aa64b9d6b51034caaa2737');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_users_user_full_user_full__users');
        Schema::dropIfExists('tl_users_user_full_user_full__chats');
        Schema::dropIfExists('tl_users_user_full_user_full');
        Schema::dropIfExists('tl_users_user_full');
    }
};
