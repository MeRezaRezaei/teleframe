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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_message_reactions_message_reactions', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_reactions')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('min')->default(false);
            $table->boolean('can_see_list')->default(false);
            $table->boolean('reactions_as_tags')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_message_reactions_message_reactions__results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_reactions_message_reactions')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_96f6543c118f157f9052');
        });
        Schema::create('tl_message_reactions_message_reactions__recent_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_reactions_message_reactions')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_88be7ba7c6d4f1062f00');
        });
        Schema::create('tl_message_reactions_message_reactions__top_reactors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_reactions_message_reactions')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_331c215e7b0bd0c17cbd');
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
