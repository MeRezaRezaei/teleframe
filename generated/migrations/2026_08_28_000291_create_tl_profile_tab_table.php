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
        Schema::create('tl_profile_tab', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_profile_tab_profile_tab_files', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_profile_tab_profile_tab_gifs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_profile_tab_profile_tab_gifts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_profile_tab_profile_tab_links', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_profile_tab_profile_tab_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_profile_tab_profile_tab_music', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_profile_tab_profile_tab_posts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_profile_tab_profile_tab_voice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_profile_tab')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_profile_tab_profile_tab_voice');
        Schema::dropIfExists('tl_profile_tab_profile_tab_posts');
        Schema::dropIfExists('tl_profile_tab_profile_tab_music');
        Schema::dropIfExists('tl_profile_tab_profile_tab_media');
        Schema::dropIfExists('tl_profile_tab_profile_tab_links');
        Schema::dropIfExists('tl_profile_tab_profile_tab_gifts');
        Schema::dropIfExists('tl_profile_tab_profile_tab_gifs');
        Schema::dropIfExists('tl_profile_tab_profile_tab_files');
        Schema::dropIfExists('tl_profile_tab');
    }
};
