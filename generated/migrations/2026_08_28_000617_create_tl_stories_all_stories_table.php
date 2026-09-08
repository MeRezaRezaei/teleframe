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
        Schema::create('tl_stories_all_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_stories_all_stories_all_stories', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_all_stories')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_more')->default(false);
            $table->integer('count');
            $table->text('state');
            $table->uuid('stealth_mode');
            $table->timestamps();
        });
        Schema::create('tl_stories_all_stories_all_stories__peer_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_all_stories_all_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0e750c9eed28f7740b5b');
        });
        Schema::create('tl_stories_all_stories_all_stories__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_all_stories_all_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e368d30514068e633e96');
        });
        Schema::create('tl_stories_all_stories_all_stories__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_all_stories_all_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_898154c0764d926e1a3c');
        });
        Schema::create('tl_stories_all_stories_all_stories_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_all_stories')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('state');
            $table->uuid('stealth_mode');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_all_stories_all_stories_not_modified');
        Schema::dropIfExists('tl_stories_all_stories_all_stories__users');
        Schema::dropIfExists('tl_stories_all_stories_all_stories__chats');
        Schema::dropIfExists('tl_stories_all_stories_all_stories__peer_stories');
        Schema::dropIfExists('tl_stories_all_stories_all_stories');
        Schema::dropIfExists('tl_stories_all_stories');
    }
};
