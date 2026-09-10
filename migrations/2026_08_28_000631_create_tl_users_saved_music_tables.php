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
        Schema::create('tl_users_saved_music_saved_music', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a1a9fb4b445a2e39056b7aff');
            $table->index('account_id', 'ix_0476be35843afb02f5623346');
        });
        Schema::create('tl_users_saved_music_saved_music__documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_03cb8f9fa3574ca842c625a4')->references('id')->on('tl_users_saved_music_saved_music')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_395c4b5427a38a7327e5');
            $table->index('account_id', 'ix_78ea5c0f0a65e739ca93ff5a');
        });
        Schema::create('tl_users_saved_music_saved_music_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a69031b8ecf43a009bf77b7f');
            $table->index('account_id', 'ix_c22ae1b00bfd0ddda516b2ef');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_users_saved_music_saved_music_not_modified');
        Schema::dropIfExists('tl_users_saved_music_saved_music__documents');
        Schema::dropIfExists('tl_users_saved_music_saved_music');
    }
};
