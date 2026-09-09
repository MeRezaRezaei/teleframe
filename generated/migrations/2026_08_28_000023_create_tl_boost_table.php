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
        Schema::create('tl_boost', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_44404b314e54050e9f7febe4');
            $table->index('account_id', 'ix_3d6e2d0d957e4afa8d659eb2');
        });
        Schema::create('tl_boost_boost', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_boost')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('gift')->default(false);
            $table->boolean('giveaway')->default(false);
            $table->boolean('unclaimed')->default(false);
            $table->text('tl_id');
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_23827ea342484da13a76d401');
            $table->integer('giveaway_msg_id')->nullable();
            $table->integer('date');
            $table->integer('expires');
            $table->text('used_gift_slug')->nullable();
            $table->integer('multiplier')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e11789b405577971a696080e');
            $table->unique(['account_id', 'tl_id'], 'ux_60144cf7a146b60c4a81');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_boost_boost');
        Schema::dropIfExists('tl_boost');
    }
};
