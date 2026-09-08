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
        Schema::create('tl_sponsored_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_sponsored_message_sponsored_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sponsored_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('recommended')->default(false);
            $table->boolean('can_report')->default(false);
            $table->binary('random_id');
            $table->text('url');
            $table->text('title');
            $table->text('message');
            $table->uuid('photo')->nullable();
            $table->uuid('media')->nullable();
            $table->uuid('color')->nullable();
            $table->text('button_text');
            $table->text('sponsor_info')->nullable();
            $table->text('additional_info')->nullable();
            $table->integer('min_display_duration')->nullable();
            $table->integer('max_display_duration')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_sponsored_message_sponsored_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sponsored_message_sponsored_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_b8db8994c29576da5160');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sponsored_message_sponsored_message__entities');
        Schema::dropIfExists('tl_sponsored_message_sponsored_message');
        Schema::dropIfExists('tl_sponsored_message');
    }
};
