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
        Schema::create('tl_stories_found_stories_found_stories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('count')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f6c224ecb75d8517cba87435');
            $table->index('account_id', 'ix_a6c53edfde0854727589aa8f');
        });
        Schema::create('tl_stories_found_stories_found_stories__stories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_stories_found_stories_found_stories', 'id', 'fk_86b9ea1075faa160022b1355')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0982d6bf5b7f22dadc0a');
            $table->index('account_id', 'ix_bd2effc2a089642455617d26');
        });
        Schema::create('tl_stories_found_stories_found_stories__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_stories_found_stories_found_stories', 'id', 'fk_6f93ea82f48dad5e4d608d8c')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e0d669afb07458ff9c42');
            $table->index('account_id', 'ix_7b4d0268db0ea205f6e20881');
        });
        Schema::create('tl_stories_found_stories_found_stories__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_stories_found_stories_found_stories', 'id', 'fk_faee5e63e45a707ed701a2bb')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d5b0e2e69a44a86d175a');
            $table->index('account_id', 'ix_5851ab9a05b24dd5e3b93138');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_found_stories_found_stories__users');
        Schema::dropIfExists('tl_stories_found_stories_found_stories__chats');
        Schema::dropIfExists('tl_stories_found_stories_found_stories__stories');
        Schema::dropIfExists('tl_stories_found_stories_found_stories');
    }
};
