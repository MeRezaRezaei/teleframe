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
        Schema::create('tl_draft_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_draft_message_draft_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_draft_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('no_webpage')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->uuid('reply_to')->nullable();
            $table->text('message');
            $table->uuid('media')->nullable();
            $table->integer('date');
            $table->bigInteger('effect')->nullable();
            $table->uuid('suggested_post')->nullable();
            $table->uuid('rich_message')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_draft_message_draft_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_draft_message_draft_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d2753e9fc5640ef20113');
        });
        Schema::create('tl_draft_message_draft_message_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_draft_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_draft_message_draft_message_empty');
        Schema::dropIfExists('tl_draft_message_draft_message__entities');
        Schema::dropIfExists('tl_draft_message_draft_message');
        Schema::dropIfExists('tl_draft_message');
    }
};
