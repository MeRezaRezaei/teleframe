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
        Schema::create('tl_message_entity_input_message_entity_mention_name', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_7d82b71b03fd1a2b7ebf1b14');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c2e33e6a11ab8e37fd356cc5');
            $table->index('account_id', 'ix_1fbf481c9e98db0ffa884705');
        });
        Schema::create('tl_message_entity_message_entity_bank_card', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b2dabe3bf88c39e396f4ab2e');
            $table->index('account_id', 'ix_c23ce30e632f728925a0a1cd');
        });
        Schema::create('tl_message_entity_message_entity_blockquote', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('collapsed')->default(false);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ccf1d0c887560bbbd7a1b3a0');
            $table->index('account_id', 'ix_eccde0fbc2bb0b83e0a8f88a');
        });
        Schema::create('tl_message_entity_message_entity_bold', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6cf2da64abf3b23f3590609b');
            $table->index('account_id', 'ix_6a3f733fc5d9eec10f5e1192');
        });
        Schema::create('tl_message_entity_message_entity_bot_command', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7692a79a794e50d2ff3e7aae');
            $table->index('account_id', 'ix_b34584f0cfed13967fd602a2');
        });
        Schema::create('tl_message_entity_message_entity_cashtag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4286c10c88b2bbd7c5e54231');
            $table->index('account_id', 'ix_787103b61466d5bbca07013c');
        });
        Schema::create('tl_message_entity_message_entity_code', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5d65a834120aa85d2297e4be');
            $table->index('account_id', 'ix_b808e867a77f00c2aff56449');
        });
        Schema::create('tl_message_entity_message_entity_custom_emoji', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('document_id')->nullable();
            $table->index('document_id', 'ix_3ca1a0bb19fca41b9975fb84');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2c4c9a8f5b9af54eb03e515c');
            $table->index('account_id', 'ix_7664cec27c337bbc591dea00');
        });
        Schema::create('tl_message_entity_message_entity_diff_delete', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_996f8b2bef17649531430e70');
            $table->index('account_id', 'ix_45c43079cf4c8fcf1bdfb6be');
        });
        Schema::create('tl_message_entity_message_entity_diff_insert', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fe1e46e92b47c404629ba630');
            $table->index('account_id', 'ix_2093899c68bcb65c3bc72c13');
        });
        Schema::create('tl_message_entity_message_entity_diff_replace', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->text('old_text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fb5b9aafd55524d2fa9a8319');
            $table->index('account_id', 'ix_cdad57aec7240268b26f7476');
        });
        Schema::create('tl_message_entity_message_entity_email', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d0735890022280525a31a52f');
            $table->index('account_id', 'ix_48d1481047a41e3bf88ef5dc');
        });
        Schema::create('tl_message_entity_message_entity_formatted_date', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('relative')->default(false);
            $table->boolean('short_time')->default(false);
            $table->boolean('long_time')->default(false);
            $table->boolean('short_date')->default(false);
            $table->boolean('long_date')->default(false);
            $table->boolean('day_of_week')->default(false);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_423448e101daed4b0a060f76');
            $table->index('account_id', 'ix_c20115a8cc56dea6408d6596');
        });
        Schema::create('tl_message_entity_message_entity_hashtag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_03a0ec0649e481cd7b4d1ab1');
            $table->index('account_id', 'ix_b57564d926d460cfe6bff9ae');
        });
        Schema::create('tl_message_entity_message_entity_italic', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_349d6a622f1bc38214604389');
            $table->index('account_id', 'ix_6e0a6a06cf1541d633f56183');
        });
        Schema::create('tl_message_entity_message_entity_mention', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0b719874207a30c7cdbe57d7');
            $table->index('account_id', 'ix_a97b969b3d62d44dcf495fe8');
        });
        Schema::create('tl_message_entity_message_entity_mention_name', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_98d5998e1b7e2d84211eb267');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9ef371fb321944aa17b7e2ac');
            $table->index('account_id', 'ix_7f9c2752c48e4506a50951d9');
        });
        Schema::create('tl_message_entity_message_entity_phone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2e44a723f6e6e33250eaffa6');
            $table->index('account_id', 'ix_d8057d9064577f04d6ca6976');
        });
        Schema::create('tl_message_entity_message_entity_pre', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->text('language')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_22841fb79d9147a8351156aa');
            $table->index('account_id', 'ix_ec5d3b8e05f64aea706dca2b');
        });
        Schema::create('tl_message_entity_message_entity_spoiler', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3aa9875caf4a1d8309c66383');
            $table->index('account_id', 'ix_63f88c6104b2e9655a8acc20');
        });
        Schema::create('tl_message_entity_message_entity_strike', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_104082211b51423aa9b4c464');
            $table->index('account_id', 'ix_3134802796cb42b97b43d44b');
        });
        Schema::create('tl_message_entity_message_entity_text_url', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_adae234b2990deafff3ef3dd');
            $table->index('account_id', 'ix_8507585fcb822c3fd042fd45');
        });
        Schema::create('tl_message_entity_message_entity_underline', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_56de2f35c7da7deae158fb78');
            $table->index('account_id', 'ix_2c0a15dfccef41716b55480a');
        });
        Schema::create('tl_message_entity_message_entity_unknown', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3ab68b40be943dc83195771d');
            $table->index('account_id', 'ix_e837bc69b77ec307592f5a9a');
        });
        Schema::create('tl_message_entity_message_entity_url', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('tl_offset')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d488467221e5120da1c72c07');
            $table->index('account_id', 'ix_dcde048c9890c84dbf0dbd35');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_entity_message_entity_url');
        Schema::dropIfExists('tl_message_entity_message_entity_unknown');
        Schema::dropIfExists('tl_message_entity_message_entity_underline');
        Schema::dropIfExists('tl_message_entity_message_entity_text_url');
        Schema::dropIfExists('tl_message_entity_message_entity_strike');
        Schema::dropIfExists('tl_message_entity_message_entity_spoiler');
        Schema::dropIfExists('tl_message_entity_message_entity_pre');
        Schema::dropIfExists('tl_message_entity_message_entity_phone');
        Schema::dropIfExists('tl_message_entity_message_entity_mention_name');
        Schema::dropIfExists('tl_message_entity_message_entity_mention');
        Schema::dropIfExists('tl_message_entity_message_entity_italic');
        Schema::dropIfExists('tl_message_entity_message_entity_hashtag');
        Schema::dropIfExists('tl_message_entity_message_entity_formatted_date');
        Schema::dropIfExists('tl_message_entity_message_entity_email');
        Schema::dropIfExists('tl_message_entity_message_entity_diff_replace');
        Schema::dropIfExists('tl_message_entity_message_entity_diff_insert');
        Schema::dropIfExists('tl_message_entity_message_entity_diff_delete');
        Schema::dropIfExists('tl_message_entity_message_entity_custom_emoji');
        Schema::dropIfExists('tl_message_entity_message_entity_code');
        Schema::dropIfExists('tl_message_entity_message_entity_cashtag');
        Schema::dropIfExists('tl_message_entity_message_entity_bot_command');
        Schema::dropIfExists('tl_message_entity_message_entity_bold');
        Schema::dropIfExists('tl_message_entity_message_entity_blockquote');
        Schema::dropIfExists('tl_message_entity_message_entity_bank_card');
        Schema::dropIfExists('tl_message_entity_input_message_entity_mention_name');
    }
};
