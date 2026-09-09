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
            $table->index('constructor_id', 'ix_e97f141d596b09f59fb19a69');
            $table->index('account_id', 'ix_e6e1db3867f25485a7a6730b');
        });
        Schema::create('tl_stories_all_stories_all_stories', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_all_stories')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_more')->default(false);
            $table->integer('count');
            $table->text('state');
            $table->uuid('stealth_mode');
            $table->index('stealth_mode', 'ix_2e5c39971181b65bc503a55c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_feee0233907b3b793ca49256');
        });
        Schema::create('tl_stories_all_stories_all_stories__peer_stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_all_stories_all_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0e750c9eed28f7740b5b');
            $table->index('account_id', 'ix_9bd28a31ba375f81c792929d');
        });
        Schema::create('tl_stories_all_stories_all_stories__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_all_stories_all_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e368d30514068e633e96');
            $table->index('account_id', 'ix_9b739c7f042645e0434e6c4b');
        });
        Schema::create('tl_stories_all_stories_all_stories__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_all_stories_all_stories')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_898154c0764d926e1a3c');
            $table->index('account_id', 'ix_2b925ace0691216e0d7bf8b4');
        });
        Schema::create('tl_stories_all_stories_all_stories_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_all_stories')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('state');
            $table->uuid('stealth_mode');
            $table->index('stealth_mode', 'ix_92bc1867cc2a9f849913c6da');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ed8b9b7549b97e5942165471');
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
