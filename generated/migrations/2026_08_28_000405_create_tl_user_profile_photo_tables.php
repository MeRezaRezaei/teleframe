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
        Schema::create('tl_user_profile_photo_user_profile_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_video')->default(false);
            $table->boolean('personal')->default(false);
            $table->bigInteger('photo_id')->nullable();
            $table->index('photo_id', 'ix_92a2bc1776c0770a606bdacd');
            $table->binary('stripped_thumb')->nullable();
            $table->integer('dc_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_134b25e5206aa2e218f8864b');
            $table->index('account_id', 'ix_f96d96a714479aa1df6e0456');
        });
        Schema::create('tl_user_profile_photo_user_profile_photo_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_88506c216a68e34cee620308');
            $table->index('account_id', 'ix_d5c3938470f40230580dcafe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_user_profile_photo_user_profile_photo_empty');
        Schema::dropIfExists('tl_user_profile_photo_user_profile_photo');
    }
};
