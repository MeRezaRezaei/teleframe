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
        Schema::create('tl_post_interaction_counters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a160f80333fdfdd971bf77c2');
            $table->index('account_id', 'ix_6a55c58129ce1bcd6108c9ef');
        });
        Schema::create('tl_post_interaction_counters_post_interaction_a4ecb5ab43c9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_post_interaction_counters')->cascadeOnDelete();
            $table->integer('msg_id');
            $table->integer('views');
            $table->integer('forwards');
            $table->integer('reactions');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8afc9fc689b137054cd549a8');
        });
        Schema::create('tl_post_interaction_counters_post_interaction_b4f5e2e1599f', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_post_interaction_counters')->cascadeOnDelete();
            $table->integer('story_id');
            $table->integer('views');
            $table->integer('forwards');
            $table->integer('reactions');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8ab87459ee99691425576c20');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_post_interaction_counters_post_interaction_b4f5e2e1599f');
        Schema::dropIfExists('tl_post_interaction_counters_post_interaction_a4ecb5ab43c9');
        Schema::dropIfExists('tl_post_interaction_counters');
    }
};
