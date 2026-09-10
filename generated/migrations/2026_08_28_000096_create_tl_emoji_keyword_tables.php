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
        Schema::create('tl_emoji_keyword_emoji_keyword', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('keyword')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ee44c459bc9778cc941c1e77');
            $table->index('account_id', 'ix_1e2e889662d7bb4567643319');
        });
        Schema::create('tl_emoji_keyword_emoji_keyword__emoticons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c3a2fb6fed8f2ea74afefcdc')->references('id')->on('tl_emoji_keyword_emoji_keyword')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d021a6ecd291dbe0348e');
            $table->index('account_id', 'ix_0e1f92c04f237b70a282d540');
        });
        Schema::create('tl_emoji_keyword_emoji_keyword_deleted', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('keyword')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c111137713890bfc020b07c3');
            $table->index('account_id', 'ix_dd095f4e34f623816360740a');
        });
        Schema::create('tl_emoji_keyword_emoji_keyword_deleted__emoticons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_bec66044bcc0b9d4c0bf8190')->references('id')->on('tl_emoji_keyword_emoji_keyword_deleted')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3b2875fde278defe7369');
            $table->index('account_id', 'ix_e996b5a4714e732a6d5a3119');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_keyword_emoji_keyword_deleted__emoticons');
        Schema::dropIfExists('tl_emoji_keyword_emoji_keyword_deleted');
        Schema::dropIfExists('tl_emoji_keyword_emoji_keyword__emoticons');
        Schema::dropIfExists('tl_emoji_keyword_emoji_keyword');
    }
};
