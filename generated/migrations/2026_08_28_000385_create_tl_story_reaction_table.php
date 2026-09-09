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
        Schema::create('tl_story_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1927a004899d651e68ab4eef');
            $table->index('account_id', 'ix_9466e9bde7b43b69f6072dd4');
        });
        Schema::create('tl_story_reaction_story_reaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_reaction')->cascadeOnDelete();
            $table->bigInteger('peer_id');
            $table->index('peer_id', 'ix_25443aa4e04dc59066243984');
            $table->integer('date');
            $table->uuid('reaction');
            $table->index('reaction', 'ix_f915d27636296772e08e611f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dd3cc63c035de2b13746e523');
        });
        Schema::create('tl_story_reaction_story_reaction_public_forward', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_reaction')->cascadeOnDelete();
            $table->uuid('message');
            $table->index('message', 'ix_238a8d24fbfd4ec0d7c97140');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d4a886711017381f561039a0');
        });
        Schema::create('tl_story_reaction_story_reaction_public_repost', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_reaction')->cascadeOnDelete();
            $table->bigInteger('peer_id');
            $table->index('peer_id', 'ix_2f9bd28698f6853ec58c4534');
            $table->uuid('story');
            $table->index('story', 'ix_e5e199888e540b47b5df0849');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bc15ad8bf83f7582bb9600ca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_reaction_story_reaction_public_repost');
        Schema::dropIfExists('tl_story_reaction_story_reaction_public_forward');
        Schema::dropIfExists('tl_story_reaction_story_reaction');
        Schema::dropIfExists('tl_story_reaction');
    }
};
