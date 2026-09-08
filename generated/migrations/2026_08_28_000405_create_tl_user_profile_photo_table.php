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
        Schema::create('tl_user_profile_photo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_user_profile_photo_user_profile_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user_profile_photo')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_video')->default(false);
            $table->boolean('personal')->default(false);
            $table->bigInteger('photo_id');
            $table->binary('stripped_thumb')->nullable();
            $table->integer('dc_id');
            $table->timestamps();
        });
        Schema::create('tl_user_profile_photo_user_profile_photo_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user_profile_photo')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_user_profile_photo_user_profile_photo_empty');
        Schema::dropIfExists('tl_user_profile_photo_user_profile_photo');
        Schema::dropIfExists('tl_user_profile_photo');
    }
};
