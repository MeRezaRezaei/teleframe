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
        Schema::create('tl_dialog_filter', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_dialog_filter_dialog_filter', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_dialog_filter')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('contacts')->default(false);
            $table->boolean('non_contacts')->default(false);
            $table->boolean('groups')->default(false);
            $table->boolean('broadcasts')->default(false);
            $table->boolean('bots')->default(false);
            $table->boolean('exclude_muted')->default(false);
            $table->boolean('exclude_read')->default(false);
            $table->boolean('exclude_archived')->default(false);
            $table->boolean('title_noanimate')->default(false);
            $table->integer('tl_id');
            $table->uuid('title');
            $table->text('emoticon')->nullable();
            $table->integer('color')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_dialog_filter_dialog_filter__pinned_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_dialog_filter_dialog_filter')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8e424f2ddad521d2b70f');
        });
        Schema::create('tl_dialog_filter_dialog_filter__include_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_dialog_filter_dialog_filter')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e3c9acad0db79300a37e');
        });
        Schema::create('tl_dialog_filter_dialog_filter__exclude_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_dialog_filter_dialog_filter')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_233ad6abffa8f0f0d002');
        });
        Schema::create('tl_dialog_filter_dialog_filter_chatlist', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_dialog_filter')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_my_invites')->default(false);
            $table->boolean('title_noanimate')->default(false);
            $table->integer('tl_id');
            $table->uuid('title');
            $table->text('emoticon')->nullable();
            $table->integer('color')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_dialog_filter_dialog_filter_chatlist__pinned_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_dialog_filter_dialog_filter_chatlist')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f29daa9a228fc3f1117d');
        });
        Schema::create('tl_dialog_filter_dialog_filter_chatlist__include_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_dialog_filter_dialog_filter_chatlist')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2db77d2f4fed603d467c');
        });
        Schema::create('tl_dialog_filter_dialog_filter_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_dialog_filter')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_dialog_filter_dialog_filter_default');
        Schema::dropIfExists('tl_dialog_filter_dialog_filter_chatlist__include_peers');
        Schema::dropIfExists('tl_dialog_filter_dialog_filter_chatlist__pinned_peers');
        Schema::dropIfExists('tl_dialog_filter_dialog_filter_chatlist');
        Schema::dropIfExists('tl_dialog_filter_dialog_filter__exclude_peers');
        Schema::dropIfExists('tl_dialog_filter_dialog_filter__include_peers');
        Schema::dropIfExists('tl_dialog_filter_dialog_filter__pinned_peers');
        Schema::dropIfExists('tl_dialog_filter_dialog_filter');
        Schema::dropIfExists('tl_dialog_filter');
    }
};
