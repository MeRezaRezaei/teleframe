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
        Schema::create('tl_premium_my_boosts_my_boosts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae45052e8501123d6f2ea83d');
            $table->index('account_id', 'ix_0b4bfbf49613a9b808b6a4ee');
        });
        Schema::create('tl_premium_my_boosts_my_boosts__my_boosts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_premium_my_boosts_my_boosts', 'id', 'fk_ebca688aece141aa3de20d6b')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_763b5fb498869234b882');
            $table->index('account_id', 'ix_fd0c938b777ff66763924de3');
        });
        Schema::create('tl_premium_my_boosts_my_boosts__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_premium_my_boosts_my_boosts', 'id', 'fk_5a416de0cb3d095b32c68182')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_68ab951cfe309eca9a3e');
            $table->index('account_id', 'ix_3272aded611df6f62ebba430');
        });
        Schema::create('tl_premium_my_boosts_my_boosts__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_premium_my_boosts_my_boosts', 'id', 'fk_d5ff3a9a6bb61ec594c341c3')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
