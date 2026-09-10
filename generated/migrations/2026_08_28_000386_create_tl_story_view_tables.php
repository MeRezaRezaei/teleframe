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
        Schema::create('tl_story_view_story_view', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('blocked_my_stories_from')->default(false);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_dc2c03bea56c1a99b9b89268');
            $table->integer('date')->nullable();
            $table->bigInteger('reaction')->nullable();
            $table->index('reaction', 'ix_be27f2ec1c209247fb6eac10');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b56050e0490b01cac4742ef9');
            $table->index('account_id', 'ix_58084e7d1b1dea460793c5e8');
        });
        Schema::create('tl_story_view_story_view_public_forward', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('blocked_my_stories_from')->default(false);
            $table->bigInteger('message')->nullable();
            $table->index('message', 'ix_60a64ba42c7e49fc8836928a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_53cb0cb4a045c753295a68a1');
            $table->index('account_id', 'ix_698da5b222ffeeeab84e09a7');
        });
        Schema::create('tl_story_view_story_view_public_repost', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('blocked_my_stories_from')->default(false);
            $table->bigInteger('peer_id')->nullable();
            $table->index('peer_id', 'ix_6ddb21de06ac744b730cb9e2');
            $table->bigInteger('story')->nullable();
            $table->index('story', 'ix_f0d1ea6ed7126ba7cd6067f8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f5738ed94aa75a6578ddfaf3');
            $table->index('account_id', 'ix_371f7cef22746cff86e1ff8c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_view_story_view_public_repost');
        Schema::dropIfExists('tl_story_view_story_view_public_forward');
        Schema::dropIfExists('tl_story_view_story_view');
    }
};
