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
        Schema::create('tf_users', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('constructor_id');
            $table->bigInteger('account_id');
            $table->bigInteger('access_hash')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('username')->nullable();
            $table->text('phone')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_self')->default(false);
            $table->boolean('is_contact')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->bigInteger('photo_id')->nullable();
            $table->text('status_type')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();
            $table->primary(['id', 'account_id']);
            $table->index('username', 'ix_tf_users_username_partial')->where('username');
            $table->index('phone', 'ix_tf_users_phone_partial')->where('phone');
            $table->index('account_id', 'ix_tf_users_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_users');
    }
};
