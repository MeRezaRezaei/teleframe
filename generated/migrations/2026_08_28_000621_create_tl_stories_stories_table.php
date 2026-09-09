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
        Schema::create('tl_stories_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a4f28caf3b6c02a202c391d9');
            $table->index('account_id', 'ix_775047b5192a87a6c727eebd');
        });
        Schema::create('tl_stories_stories_stories', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_stories')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c0df55bc1db37dad863e0cfb');
        });
        Schema::create('tl_stories_stories_stories__stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_stories_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_231e79ab7ab5055861df');
            $table->index('account_id', 'ix_f15440c04ceab55d7b9d35af');
        });
        Schema::create('tl_stories_stories_stories__pinned_to_top', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_stories_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d5631ac5aa6621ad6cfd');
            $table->index('account_id', 'ix_0fd25d5b109163df29810a16');
        });
        Schema::create('tl_stories_stories_stories__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_stories_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d93a2a2d690e0435dcbd');
            $table->index('account_id', 'ix_a24da4b953b1d58d8b30fe4c');
        });
        Schema::create('tl_stories_stories_stories__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_stories_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d5cbd7328769d62414f1');
            $table->index('account_id', 'ix_133711891c217c337eeed228');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_stories_stories__users');
        Schema::dropIfExists('tl_stories_stories_stories__chats');
        Schema::dropIfExists('tl_stories_stories_stories__pinned_to_top');
        Schema::dropIfExists('tl_stories_stories_stories__stories');
        Schema::dropIfExists('tl_stories_stories_stories');
        Schema::dropIfExists('tl_stories_stories');
    }
};
