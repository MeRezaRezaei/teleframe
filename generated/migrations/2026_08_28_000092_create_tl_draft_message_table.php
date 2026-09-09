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
            $table->index('constructor_id', 'ix_7d88d4ccee0e23ba1ed13138');
            $table->index('account_id', 'ix_09668460409ea544826248ce');
        });
        Schema::create('tl_draft_message_draft_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_draft_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('no_webpage')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->uuid('reply_to')->nullable();
            $table->index('reply_to', 'ix_1284e3efb4786f48bb1f31df');
            $table->text('message');
            $table->uuid('media')->nullable();
            $table->index('media', 'ix_28260d5d86a6bd1804632bc2');
            $table->integer('date');
            $table->bigInteger('effect')->nullable();
            $table->uuid('suggested_post')->nullable();
            $table->index('suggested_post', 'ix_f503acb00f4ee9ba87c19a90');
            $table->uuid('rich_message')->nullable();
            $table->index('rich_message', 'ix_781b71d7436ecd7352f7f7fb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3ae32948dab6162be8a281f4');
        });
        Schema::create('tl_draft_message_draft_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_draft_message_draft_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d2753e9fc5640ef20113');
            $table->index('account_id', 'ix_6b580886b9ff37538bed7a5e');
        });
        Schema::create('tl_draft_message_draft_message_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_draft_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d1ff0a56a1ba53139bf10bc0');
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
