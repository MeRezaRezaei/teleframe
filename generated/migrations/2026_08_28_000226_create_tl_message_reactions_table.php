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
        Schema::create('tl_message_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4215f065d82237bd11447d34');
            $table->index('account_id', 'ix_2f9b38c18c5ab21cba20a477');
        });
        Schema::create('tl_message_reactions_message_reactions', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_reactions')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('min')->default(false);
            $table->boolean('can_see_list')->default(false);
            $table->boolean('reactions_as_tags')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7c8275b7d453648105c32e9b');
        });
        Schema::create('tl_message_reactions_message_reactions__results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_reactions_message_reactions')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_96f6543c118f157f9052');
            $table->index('account_id', 'ix_3d70138d1835014f55a253e1');
        });
        Schema::create('tl_message_reactions_message_reactions__recent_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_reactions_message_reactions')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_88be7ba7c6d4f1062f00');
            $table->index('account_id', 'ix_03c0fcb6f8265c5b13810a52');
        });
        Schema::create('tl_message_reactions_message_reactions__top_reactors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_reactions_message_reactions')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_331c215e7b0bd0c17cbd');
            $table->index('account_id', 'ix_7ca0e562d6b231c908300fec');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_reactions_message_reactions__top_reactors');
        Schema::dropIfExists('tl_message_reactions_message_reactions__recent_reactions');
        Schema::dropIfExists('tl_message_reactions_message_reactions__results');
        Schema::dropIfExists('tl_message_reactions_message_reactions');
        Schema::dropIfExists('tl_message_reactions');
    }
};
