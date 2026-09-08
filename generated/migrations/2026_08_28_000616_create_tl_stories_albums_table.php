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
        Schema::create('tl_stories_albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_stories_albums_albums', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_albums')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->timestamps();
        });
        Schema::create('tl_stories_albums_albums__albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_albums_albums')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0624bc3defede46a54d0');
        });
        Schema::create('tl_stories_albums_albums_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_albums')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_albums_albums_not_modified');
        Schema::dropIfExists('tl_stories_albums_albums__albums');
        Schema::dropIfExists('tl_stories_albums_albums');
        Schema::dropIfExists('tl_stories_albums');
    }
};
