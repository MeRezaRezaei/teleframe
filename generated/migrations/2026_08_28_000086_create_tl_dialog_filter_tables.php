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
        Schema::create('tl_dialog_filter_dialog_filter', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
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
            $table->integer('tl_id')->nullable();
            $table->bigInteger('title')->nullable();
            $table->index('title', 'ix_f4092218c89c8ce00c165c87');
            $table->text('emoticon')->nullable();
            $table->integer('color')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_51ccda8a0278c806f76a45ea');
            $table->index('account_id', 'ix_da5be758a39917b5aa6ac1d4');
            $table->unique(['account_id'], 'ux_4abd3aec2129c7006f5e');
        });
        Schema::create('tl_dialog_filter_dialog_filter__pinned_peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_dialog_filter_dialog_filter', 'id', 'fk_2e915772fda9d6dfd87fad49')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8e424f2ddad521d2b70f');
            $table->index('account_id', 'ix_eeb82a3756529037826d38ce');
        });
        Schema::create('tl_dialog_filter_dialog_filter__include_peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_dialog_filter_dialog_filter', 'id', 'fk_517277ab189fdfa1f15028b3')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e3c9acad0db79300a37e');
            $table->index('account_id', 'ix_967200b9ad269f74d3ec209f');
        });
        Schema::create('tl_dialog_filter_dialog_filter__exclude_peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_dialog_filter_dialog_filter', 'id', 'fk_1e076a1609f62b53687dc937')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_233ad6abffa8f0f0d002');
            $table->index('account_id', 'ix_024261376d31b1fa0b7a7532');
        });
        Schema::create('tl_dialog_filter_dialog_filter_chatlist', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_my_invites')->default(false);
            $table->boolean('title_noanimate')->default(false);
            $table->integer('tl_id')->nullable();
            $table->bigInteger('title')->nullable();
            $table->index('title', 'ix_117e79ac634d2b5585af8293');
            $table->text('emoticon')->nullable();
            $table->integer('color')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_db78b662cd90eb5018b1a8c1');
            $table->index('account_id', 'ix_662f86cdb682fd6b69d4db50');
            $table->unique(['account_id'], 'ux_5355cb106964e12e3421');
        });
        Schema::create('tl_dialog_filter_dialog_filter_chatlist__pinned_peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_dialog_filter_dialog_filter_chatlist', 'id', 'fk_50fb6b073eecf250fc2bb672')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f29daa9a228fc3f1117d');
            $table->index('account_id', 'ix_7d9afb72d65ef196d4eec7f5');
        });
        Schema::create('tl_dialog_filter_dialog_filter_chatlist__include_peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_dialog_filter_dialog_filter_chatlist', 'id', 'fk_cf26b16c212b1bb55fd3f1cd')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2db77d2f4fed603d467c');
            $table->index('account_id', 'ix_a551c5b8730a4dacec6a1955');
        });
        Schema::create('tl_dialog_filter_dialog_filter_default', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_85fb8361aef1b2c19f2c1386');
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
    }
};
