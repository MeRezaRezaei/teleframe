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
        Schema::create('tl_story_view', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bf2bab2c252b58bb2d492676');
            $table->index('account_id', 'ix_6761210c1d1445b111706c0f');
        });
        Schema::create('tl_story_view_story_view', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_view')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('blocked_my_stories_from')->default(false);
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_dc2c03bea56c1a99b9b89268');
            $table->integer('date');
            $table->uuid('reaction')->nullable();
            $table->index('reaction', 'ix_be27f2ec1c209247fb6eac10');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_58084e7d1b1dea460793c5e8');
        });
        Schema::create('tl_story_view_story_view_public_forward', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_view')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('blocked_my_stories_from')->default(false);
            $table->uuid('message');
            $table->index('message', 'ix_60a64ba42c7e49fc8836928a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_698da5b222ffeeeab84e09a7');
        });
        Schema::create('tl_story_view_story_view_public_repost', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_view')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('blocked_my_stories_from')->default(false);
            $table->bigInteger('peer_id');
            $table->index('peer_id', 'ix_6ddb21de06ac744b730cb9e2');
            $table->uuid('story');
            $table->index('story', 'ix_f0d1ea6ed7126ba7cd6067f8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_371f7cef22746cff86e1ff8c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_view_story_view_public_repost');
        Schema::dropIfExists('tl_story_view_story_view_public_forward');
        Schema::dropIfExists('tl_story_view_story_view');
        Schema::dropIfExists('tl_story_view');
    }
};
