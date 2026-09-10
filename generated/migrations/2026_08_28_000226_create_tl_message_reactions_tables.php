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
        Schema::create('tl_message_reactions_message_reactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('min')->default(false);
            $table->boolean('can_see_list')->default(false);
            $table->boolean('reactions_as_tags')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2e2cb4f59ad6ed78ff7bb0e3');
            $table->index('account_id', 'ix_7c8275b7d453648105c32e9b');
        });
        Schema::create('tl_message_reactions_message_reactions__results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_reactions_message_reactions', 'id', 'fk_41adfca1273f15b59464ae75')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_96f6543c118f157f9052');
            $table->index('account_id', 'ix_3d70138d1835014f55a253e1');
        });
        Schema::create('tl_message_reactions_message_reactions__recent_reactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_reactions_message_reactions', 'id', 'fk_ee5ce4147a231b37d1030938')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_88be7ba7c6d4f1062f00');
            $table->index('account_id', 'ix_03c0fcb6f8265c5b13810a52');
        });
        Schema::create('tl_message_reactions_message_reactions__top_reactors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_reactions_message_reactions', 'id', 'fk_cc89ec29b227d3dca71b1c8e')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
