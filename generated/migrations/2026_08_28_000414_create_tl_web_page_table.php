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
        Schema::create('tl_web_page', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_web_page_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_large_media')->default(false);
            $table->boolean('video_cover_photo')->default(false);
            $table->bigInteger('tl_id');
            $table->text('url');
            $table->text('display_url');
            $table->integer('hash');
            $table->text('tl_type')->nullable();
            $table->text('site_name')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->uuid('photo')->nullable();
            $table->text('embed_url')->nullable();
            $table->text('embed_type')->nullable();
            $table->integer('embed_width')->nullable();
            $table->integer('embed_height')->nullable();
            $table->integer('duration')->nullable();
            $table->text('author')->nullable();
            $table->uuid('document')->nullable();
            $table->uuid('cached_page')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_web_page_web_page__attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_web_page_web_page')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1bed9003520362f54a3b');
        });
        Schema::create('tl_web_page_web_page_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id');
            $table->text('url')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_web_page_web_page_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('cached_page_views')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_web_page_web_page_pending', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_page')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_id');
            $table->text('url')->nullable();
            $table->integer('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_page_web_page_pending');
        Schema::dropIfExists('tl_web_page_web_page_not_modified');
        Schema::dropIfExists('tl_web_page_web_page_empty');
        Schema::dropIfExists('tl_web_page_web_page__attributes');
        Schema::dropIfExists('tl_web_page_web_page');
        Schema::dropIfExists('tl_web_page');
    }
};
