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
        Schema::create('tl_stats_public_forwards_public_forwards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('count')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6921ee6225587ab65ff006df');
            $table->index('account_id', 'ix_3f21e5ce3f028ad1566e87ce');
        });
        Schema::create('tl_stats_public_forwards_public_forwards__forwards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ec86ca2a577a67827a32f967')->references('id')->on('tl_stats_public_forwards_public_forwards')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a496ab46e594386b2cc9');
            $table->index('account_id', 'ix_6af7d49aae2b657494daa64e');
        });
        Schema::create('tl_stats_public_forwards_public_forwards__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_736ed7c27acae3237a990cba')->references('id')->on('tl_stats_public_forwards_public_forwards')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_22aa0955bf91f8353779');
            $table->index('account_id', 'ix_e7e07275c6224de9f70dfb9f');
        });
        Schema::create('tl_stats_public_forwards_public_forwards__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_13ecff5f2e41776f8b184e2b')->references('id')->on('tl_stats_public_forwards_public_forwards')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_10517a2b4415a5ff6ce9');
            $table->index('account_id', 'ix_bebed8070329337b840ec992');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_public_forwards_public_forwards__users');
        Schema::dropIfExists('tl_stats_public_forwards_public_forwards__chats');
        Schema::dropIfExists('tl_stats_public_forwards_public_forwards__forwards');
        Schema::dropIfExists('tl_stats_public_forwards_public_forwards');
    }
};
