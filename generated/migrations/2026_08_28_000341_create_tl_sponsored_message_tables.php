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
        Schema::create('tl_sponsored_message_sponsored_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('recommended')->default(false);
            $table->boolean('can_report')->default(false);
            $table->binary('random_id')->nullable();
            $table->text('url')->nullable();
            $table->text('title')->nullable();
            $table->text('message')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_67ed8c8d85a056ceb8855687');
            $table->bigInteger('media')->nullable();
            $table->index('media', 'ix_245f08a71486244c511a1902');
            $table->bigInteger('color')->nullable();
            $table->index('color', 'ix_b7adaa68a374fe022af2cb8f');
            $table->text('button_text')->nullable();
            $table->text('sponsor_info')->nullable();
            $table->text('additional_info')->nullable();
            $table->integer('min_display_duration')->nullable();
            $table->integer('max_display_duration')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8e2534072e9aad1b7d795a0d');
            $table->index('account_id', 'ix_21a5b68152d7c011fcf9be35');
        });
        Schema::create('tl_sponsored_message_sponsored_message__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_sponsored_message_sponsored_message', 'id', 'fk_9bf391fb34953f41687bd87f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b8db8994c29576da5160');
            $table->index('account_id', 'ix_1836a5d93dc5ed1532586da8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sponsored_message_sponsored_message__entities');
        Schema::dropIfExists('tl_sponsored_message_sponsored_message');
    }
};
