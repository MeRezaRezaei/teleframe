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
        Schema::create('tl_text_with_entities_text_with_entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ff1d6da5b1e72f45cb8ebaf1');
            $table->index('account_id', 'ix_a78645f3238405ad85302135');
        });
        Schema::create('tl_text_with_entities_text_with_entities__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_be40811dbbcf3673d4b87ddd')->references('id')->on('tl_text_with_entities_text_with_entities')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ba4a8a6a9f85a4389046');
            $table->index('account_id', 'ix_5dd0f403adc22d16dc004e43');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_text_with_entities_text_with_entities__entities');
        Schema::dropIfExists('tl_text_with_entities_text_with_entities');
    }
};
