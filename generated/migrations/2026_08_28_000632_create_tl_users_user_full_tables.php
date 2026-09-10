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
        Schema::create('tl_users_user_full_user_full', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('full_user')->nullable();
            $table->index('full_user', 'ix_cfded6d34d5161ff5bb7e54d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dec6ced3dd602246e3a0f4f7');
            $table->index('account_id', 'ix_8ed1b416bbff5c5b4a910040');
        });
        Schema::create('tl_users_user_full_user_full__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_08a743c27849661c07b8beed')->references('id')->on('tl_users_user_full_user_full')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c5a28a0d556e08661c73');
            $table->index('account_id', 'ix_a61bcd8b8815c43c1a923747');
        });
        Schema::create('tl_users_user_full_user_full__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_fd1ec5bdfe85a86820adc8c9')->references('id')->on('tl_users_user_full_user_full')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
