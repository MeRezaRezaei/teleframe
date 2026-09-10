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
        Schema::create('tl_messages_filter_input_messages_filter_chat_photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_769549195f1b52b2919e0946');
            $table->index('account_id', 'ix_375f73a5cd2979dcd8883d3e');
        });
        Schema::create('tl_messages_filter_input_messages_filter_contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d59e19355ab5b558cfdfb549');
            $table->index('account_id', 'ix_29a67b420fb51b7cb8f82157');
        });
        Schema::create('tl_messages_filter_input_messages_filter_document', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e50e8d9e43ed8b0482c030b8');
            $table->index('account_id', 'ix_63043cb7c8aa1cf3e48714de');
        });
        Schema::create('tl_messages_filter_input_messages_filter_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8d16dda90ef9920ec76b7d2a');
            $table->index('account_id', 'ix_2b0d80761cdf5ae16c95d137');
        });
        Schema::create('tl_messages_filter_input_messages_filter_geo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9ea20e98be9ad14e7a67fb03');
            $table->index('account_id', 'ix_7b9c0bacedb2d77be364f2e8');
        });
        Schema::create('tl_messages_filter_input_messages_filter_gif', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_54ddff03a2f359604ae694b4');
            $table->index('account_id', 'ix_6351c9e569acea7bd20279c8');
        });
        Schema::create('tl_messages_filter_input_messages_filter_music', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2d55dded227073a1673b5a05');
            $table->index('account_id', 'ix_550d0bf99af23e53c592e453');
        });
        Schema::create('tl_messages_filter_input_messages_filter_my_mentions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_38726a640ea327df3cc0f643');
            $table->index('account_id', 'ix_09db1067cb65b3ed302405ae');
        });
        Schema::create('tl_messages_filter_input_messages_filter_phone_calls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('missed')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_15cf947fbb1a30d342624978');
            $table->index('account_id', 'ix_827b0a53449a856de0c2aa93');
        });
        Schema::create('tl_messages_filter_input_messages_filter_photo_video', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d1b40ef1fb19bcdc9cec6c06');
            $table->index('account_id', 'ix_058d034c13dd5f4327b8551a');
        });
        Schema::create('tl_messages_filter_input_messages_filter_photos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_936edbfbc8a389ace0f12460');
            $table->index('account_id', 'ix_8091bcd2e9fbdcd9e0ac723e');
        });
        Schema::create('tl_messages_filter_input_messages_filter_pinned', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_92c6ea05db3f2f1cbe66f7ed');
            $table->index('account_id', 'ix_fb3a81cc27abf291847ae7ad');
        });
        Schema::create('tl_messages_filter_input_messages_filter_poll', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c72c8ff619fe9eba7d73fd8a');
            $table->index('account_id', 'ix_72b0d32a94860125ef15ea17');
        });
        Schema::create('tl_messages_filter_input_messages_filter_round_video', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_76fad3d24e35ed40db8709c7');
            $table->index('account_id', 'ix_0675a4928596974159c60691');
        });
        Schema::create('tl_messages_filter_input_messages_filter_round_voice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_19888b9416aa94da2c40afc5');
            $table->index('account_id', 'ix_00e9e937d05c6f68b1a91f31');
        });
        Schema::create('tl_messages_filter_input_messages_filter_url', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0970403035dc8a045cca3e4c');
            $table->index('account_id', 'ix_d96dd547bf548d0b1428bbd1');
        });
        Schema::create('tl_messages_filter_input_messages_filter_video', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_df5470827f06fd40e7222fd6');
            $table->index('account_id', 'ix_e96db0fb52535a4c2b7e9486');
        });
        Schema::create('tl_messages_filter_input_messages_filter_voice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4a30f57f6864f4afe49a4b40');
            $table->index('account_id', 'ix_0ab30569b7e2a323288fbbed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_voice');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_video');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_url');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_round_voice');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_round_video');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_poll');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_pinned');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_photos');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_photo_video');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_phone_calls');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_my_mentions');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_music');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_gif');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_geo');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_empty');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_document');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_contacts');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_chat_photos');
    }
};
