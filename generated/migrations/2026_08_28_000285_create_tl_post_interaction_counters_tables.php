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
        Schema::create('tl_post_interaction_counters_post_interaction_a4ecb5ab43c9', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('msg_id')->nullable();
            $table->integer('views')->nullable();
            $table->integer('forwards')->nullable();
            $table->integer('reactions')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f8ffa5ba16e90dc33c15bd5f');
            $table->index('account_id', 'ix_8afc9fc689b137054cd549a8');
        });
        Schema::create('tl_post_interaction_counters_post_interaction_b4f5e2e1599f', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('story_id')->nullable();
            $table->integer('views')->nullable();
            $table->integer('forwards')->nullable();
            $table->integer('reactions')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bdf42decc88c00dddb9be545');
            $table->index('account_id', 'ix_8ab87459ee99691425576c20');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_post_interaction_counters_post_interaction_b4f5e2e1599f');
        Schema::dropIfExists('tl_post_interaction_counters_post_interaction_a4ecb5ab43c9');
    }
};
