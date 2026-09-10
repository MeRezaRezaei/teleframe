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
        Schema::create('tl_emoji_keywords_difference_emoji_keywords_difference', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('lang_code')->nullable();
            $table->integer('from_version')->nullable();
            $table->integer('version')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae22364e8ebb113e60acf9c2');
            $table->index('account_id', 'ix_f145e462e4177f56cdb1cd6a');
        });
        Schema::create('tl_emoji_keywords_difference_emoji_keywords_d_8bb0c8308884', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_5f58f5a7d2ab70371f059f72')->references('id')->on('tl_emoji_keywords_difference_emoji_keywords_difference')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8da1631f865daff3e450');
            $table->index('account_id', 'ix_5309969e6cb5f9b48af5554f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_keywords_difference_emoji_keywords_d_8bb0c8308884');
        Schema::dropIfExists('tl_emoji_keywords_difference_emoji_keywords_difference');
    }
};
