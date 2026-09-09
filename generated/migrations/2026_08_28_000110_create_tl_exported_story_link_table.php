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
        Schema::create('tl_exported_story_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1c2f38c57f60862682658fda');
            $table->index('account_id', 'ix_ab8c3994480d84de3b2d4ab2');
        });
        Schema::create('tl_exported_story_link_exported_story_link', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_exported_story_link')->cascadeOnDelete();
            $table->text('link');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_20e986856588f871039d1505');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_exported_story_link_exported_story_link');
        Schema::dropIfExists('tl_exported_story_link');
    }
};
