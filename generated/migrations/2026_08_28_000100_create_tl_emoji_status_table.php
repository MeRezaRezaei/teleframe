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
        Schema::create('tl_emoji_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_emoji_status_emoji_status', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('document_id');
            $table->integer('until')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_emoji_status_emoji_status_collectible', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('collectible_id');
            $table->bigInteger('document_id');
            $table->text('title');
            $table->text('slug');
            $table->bigInteger('pattern_document_id');
            $table->integer('center_color');
            $table->integer('edge_color');
            $table->integer('pattern_color');
            $table->integer('text_color');
            $table->integer('until')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_emoji_status_emoji_status_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_status')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_emoji_status_input_emoji_status_collectible', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('collectible_id');
            $table->integer('until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_status_input_emoji_status_collectible');
        Schema::dropIfExists('tl_emoji_status_emoji_status_empty');
        Schema::dropIfExists('tl_emoji_status_emoji_status_collectible');
        Schema::dropIfExists('tl_emoji_status_emoji_status');
        Schema::dropIfExists('tl_emoji_status');
    }
};
