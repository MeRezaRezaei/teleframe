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
        Schema::create('tl_text_with_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_text_with_entities_text_with_entities', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_text_with_entities')->cascadeOnDelete();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_text_with_entities_text_with_entities__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_text_with_entities_text_with_entities')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ba4a8a6a9f85a4389046');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_text_with_entities_text_with_entities__entities');
        Schema::dropIfExists('tl_text_with_entities_text_with_entities');
        Schema::dropIfExists('tl_text_with_entities');
    }
};
