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
        Schema::create('tl_user_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_user_status_user_status_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user_status')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_user_status_user_status_last_month', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('by_me')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_user_status_user_status_last_week', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('by_me')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_user_status_user_status_offline', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user_status')->cascadeOnDelete();
            $table->integer('was_online');
            $table->timestamps();
        });
        Schema::create('tl_user_status_user_status_online', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user_status')->cascadeOnDelete();
            $table->integer('expires');
            $table->timestamps();
        });
        Schema::create('tl_user_status_user_status_recently', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('by_me')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_user_status_user_status_recently');
        Schema::dropIfExists('tl_user_status_user_status_online');
        Schema::dropIfExists('tl_user_status_user_status_offline');
        Schema::dropIfExists('tl_user_status_user_status_last_week');
        Schema::dropIfExists('tl_user_status_user_status_last_month');
        Schema::dropIfExists('tl_user_status_user_status_empty');
        Schema::dropIfExists('tl_user_status');
    }
};
