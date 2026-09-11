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
        Schema::create('tf_channels', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('constructor_id');
            $table->bigInteger('account_id');
            $table->bigInteger('access_hash')->nullable();
            $table->text('title')->nullable();
            $table->text('username')->nullable();
            $table->integer('date')->nullable();
            $table->integer('participants_count')->nullable();
            $table->boolean('is_broadcast')->default(false);
            $table->boolean('is_megagroup')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_restricted')->default(false);
            $table->boolean('is_left')->default(false);
            $table->boolean('is_forum')->default(false);
            $table->text('restriction_reason')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();
            $table->primary(['id', 'account_id']);
            $table->index('username', 'ix_tf_channels_username_partial')->where('username');
            $table->index('account_id', 'ix_tf_channels_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_channels');
    }
};
