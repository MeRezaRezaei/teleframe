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
        Schema::create('tl_draft_message_draft_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('no_webpage')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->bigInteger('reply_to')->nullable();
            $table->index('reply_to', 'ix_1284e3efb4786f48bb1f31df');
            $table->text('message')->nullable();
            $table->bigInteger('media')->nullable();
            $table->index('media', 'ix_28260d5d86a6bd1804632bc2');
            $table->integer('date')->nullable();
            $table->bigInteger('effect')->nullable();
            $table->bigInteger('suggested_post')->nullable();
            $table->index('suggested_post', 'ix_f503acb00f4ee9ba87c19a90');
            $table->bigInteger('rich_message')->nullable();
            $table->index('rich_message', 'ix_781b71d7436ecd7352f7f7fb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f5b1339454f11eff7daa481c');
            $table->index('account_id', 'ix_3ae32948dab6162be8a281f4');
        });
        Schema::create('tl_draft_message_draft_message__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_draft_message_draft_message', 'id', 'fk_c1f9db8896bb0c13aa0da24d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d2753e9fc5640ef20113');
            $table->index('account_id', 'ix_6b580886b9ff37538bed7a5e');
        });
        Schema::create('tl_draft_message_draft_message_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4c0f427f4bf7e793464c4b30');
            $table->index('account_id', 'ix_d1ff0a56a1ba53139bf10bc0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_draft_message_draft_message_empty');
        Schema::dropIfExists('tl_draft_message_draft_message__entities');
        Schema::dropIfExists('tl_draft_message_draft_message');
    }
};
