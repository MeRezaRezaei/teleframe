<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_users', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('account_id');
            $table->bigInteger('access_hash')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->string('username', 64)->nullable();
            $table->string('phone', 32)->nullable();
            $table->boolean('is_self')->default(false);
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_contact')->default(false);
            $table->boolean('is_mutual_contact')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_restricted')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_scam')->default(false);
            $table->boolean('is_fake')->default(false);
            $table->bigInteger('photo_id')->nullable();
            $table->text('status_type')->nullable();
            $table->text('lang_code')->nullable();
            $table->text('restriction_reason')->nullable();
            $table->integer('bot_info_version')->nullable();
            $table->text('bot_inline_placeholder')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();

            $table->primary(['id', 'account_id']);
            $table->index('username', 'ix_tf_users_username')->where('username');
            $table->index('phone', 'ix_tf_users_phone')->where('phone');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_users');
    }
};
