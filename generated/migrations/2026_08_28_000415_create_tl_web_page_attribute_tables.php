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
        Schema::create('tl_web_page_attribute_web_page_attribute_ai_compose_tone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('emoji_id')->nullable();
            $table->index('emoji_id', 'ix_1f033da7fe0b3c7f54fe31a6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3e47d74c91fe16d9ef2ccaed');
            $table->index('account_id', 'ix_b408a008f37388374015e2c7');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_star_gift_auction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('gift')->nullable();
            $table->index('gift', 'ix_08e80c51dac588593889338d');
            $table->integer('end_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_77126ca1b281aafc916a989c');
            $table->index('account_id', 'ix_1dc69b20b7cec6bb26133264');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_star_0f76dd30baaf', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ebcea4c7f090f281d28ac61b');
            $table->index('account_id', 'ix_54a36224ea9ffdb4360b9f73');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_star_17202adcc3bb', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_80e4a29e2a00dd0e728f9e6d')->references('id')->on('tl_web_page_attribute_web_page_attribute_star_0f76dd30baaf')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3174fdb2013fa3926a77');
            $table->index('account_id', 'ix_dbe10031d9876d3ffb805146');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_sticker_set', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('emojis')->default(false);
            $table->boolean('text_color')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_29c7bad30b17a034556a71bb');
            $table->index('account_id', 'ix_f2d31b21f0e5ea7819c21bea');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_stic_5d4fa9f0c49f', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_e32ad3bb7fb5d19029354dee')->references('id')->on('tl_web_page_attribute_web_page_attribute_sticker_set')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e3b08dcbfabfde675ba7');
            $table->index('account_id', 'ix_654d1a1646e9025d43dc09e3');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_story', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_503f1fa8faf9da6c4f77712a');
            $table->integer('tl_id')->nullable();
            $table->bigInteger('story')->nullable();
            $table->index('story', 'ix_5130385a9fad32a3cffc10cd');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e8178bc6b25c904c64a41201');
            $table->index('account_id', 'ix_a8f8b416919a15e1b22e93ae');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_theme', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('settings')->nullable();
            $table->index('settings', 'ix_f9d5f81a0ee3e92e2121c74e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cb5d4a5274b1a295995de51c');
            $table->index('account_id', 'ix_6a3e07c2fed99e1f9c6c8368');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_theme__documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_8b5a537eedfb29c153e6bee7')->references('id')->on('tl_web_page_attribute_web_page_attribute_theme')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_387b647caa8e1462fdc4');
            $table->index('account_id', 'ix_634a24472115dff7d5fa07f2');
        });
        Schema::create('tl_web_page_attribute_web_page_attribute_unique_star_gift', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('gift')->nullable();
            $table->index('gift', 'ix_812f47e019b382df32a9be12');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_152911801f2c9767d15dde42');
            $table->index('account_id', 'ix_98b7a2f6942c066a51bb9fb8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_unique_star_gift');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_theme__documents');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_theme');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_story');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_stic_5d4fa9f0c49f');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_sticker_set');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_star_17202adcc3bb');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_star_0f76dd30baaf');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_star_gift_auction');
        Schema::dropIfExists('tl_web_page_attribute_web_page_attribute_ai_compose_tone');
    }
};
