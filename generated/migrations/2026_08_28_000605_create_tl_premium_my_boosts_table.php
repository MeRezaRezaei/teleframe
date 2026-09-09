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
        Schema::create('tl_premium_my_boosts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_fd15d45b22f7f974c94e20b6');
            $table->index('account_id', 'ix_2e5734b8de69c7b94732d254');
        });
        Schema::create('tl_premium_my_boosts_my_boosts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_premium_my_boosts')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0b4bfbf49613a9b808b6a4ee');
        });
        Schema::create('tl_premium_my_boosts_my_boosts__my_boosts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_premium_my_boosts_my_boosts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_763b5fb498869234b882');
            $table->index('account_id', 'ix_fd0c938b777ff66763924de3');
        });
        Schema::create('tl_premium_my_boosts_my_boosts__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_premium_my_boosts_my_boosts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_68ab951cfe309eca9a3e');
            $table->index('account_id', 'ix_3272aded611df6f62ebba430');
        });
        Schema::create('tl_premium_my_boosts_my_boosts__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_premium_my_boosts_my_boosts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b2f47e9624101cbbd57d');
            $table->index('account_id', 'ix_96c771867a6ceeebc1157522');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_premium_my_boosts_my_boosts__users');
        Schema::dropIfExists('tl_premium_my_boosts_my_boosts__chats');
        Schema::dropIfExists('tl_premium_my_boosts_my_boosts__my_boosts');
        Schema::dropIfExists('tl_premium_my_boosts_my_boosts');
        Schema::dropIfExists('tl_premium_my_boosts');
    }
};
