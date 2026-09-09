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
            $table->index('constructor_id', 'ix_3d29324e661bb4485c9422d6');
            $table->index('account_id', 'ix_7dbe5b6c04966d46b9a72d77');
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
            $table->index('title', 'ix_f4092218c89c8ce00c165c87');
            $table->text('emoticon')->nullable();
            $table->integer('color')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_da5be758a39917b5aa6ac1d4');
            $table->unique(['account_id', 'tl_id'], 'ux_4abd3aec2129c7006f5e');
        });
        Schema::create('tl_dialog_filter_dialog_filter__pinned_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_dialog_filter_dialog_filter')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8e424f2ddad521d2b70f');
            $table->index('account_id', 'ix_eeb82a3756529037826d38ce');
        });
        Schema::create('tl_dialog_filter_dialog_filter__include_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_dialog_filter_dialog_filter')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e3c9acad0db79300a37e');
            $table->index('account_id', 'ix_967200b9ad269f74d3ec209f');
        });
        Schema::create('tl_dialog_filter_dialog_filter__exclude_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_dialog_filter_dialog_filter')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_233ad6abffa8f0f0d002');
            $table->index('account_id', 'ix_024261376d31b1fa0b7a7532');
        });
        Schema::create('tl_dialog_filter_dialog_filter_chatlist', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_dialog_filter')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_my_invites')->default(false);
            $table->boolean('title_noanimate')->default(false);
            $table->integer('tl_id');
            $table->uuid('title');
            $table->index('title', 'ix_117e79ac634d2b5585af8293');
            $table->text('emoticon')->nullable();
            $table->integer('color')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_662f86cdb682fd6b69d4db50');
            $table->unique(['account_id', 'tl_id'], 'ux_5355cb106964e12e3421');
        });
        Schema::create('tl_dialog_filter_dialog_filter_chatlist__pinned_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_dialog_filter_dialog_filter_chatlist')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f29daa9a228fc3f1117d');
            $table->index('account_id', 'ix_7d9afb72d65ef196d4eec7f5');
        });
        Schema::create('tl_dialog_filter_dialog_filter_chatlist__include_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_dialog_filter_dialog_filter_chatlist')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2db77d2f4fed603d467c');
            $table->index('account_id', 'ix_a551c5b8730a4dacec6a1955');
        });
        Schema::create('tl_dialog_filter_dialog_filter_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_dialog_filter')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_71f4cc6643ba7d7771cbee91');
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
