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
        Schema::create('tl_keyboard_button_input_keyboard_button_request_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('name_requested')->default(false);
            $table->boolean('username_requested')->default(false);
            $table->boolean('photo_requested')->default(false);
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_f60df203c5b67db58deaf529');
            $table->text('text')->nullable();
            $table->integer('button_id')->nullable();
            $table->bigInteger('peer_type')->nullable();
            $table->index('peer_type', 'ix_617cbc0bc647bffb07da207e');
            $table->integer('max_quantity')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c3242b6ef7336816e022b07b');
            $table->index('account_id', 'ix_63dc49599f74690e7d3e1ae9');
        });
        Schema::create('tl_keyboard_button_input_keyboard_button_url_auth', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('request_write_access')->default(false);
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_29e52bf6bc2359909bee8d71');
            $table->text('text')->nullable();
            $table->text('fwd_text')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('bot')->nullable();
            $table->index('bot', 'ix_9944e313ecc2441fd0f3a33d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_385d01fb38574c1c2a6b54de');
            $table->index('account_id', 'ix_4801577abcb6ea0061702507');
        });
        Schema::create('tl_keyboard_button_input_keyboard_button_user_profile', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_7e3e96c6daab3383d7424bbb');
            $table->text('text')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_655bf220d28eb5753ab83a99');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7e32b04f605493c95b369169');
            $table->index('account_id', 'ix_98f04ad1fba1d7f4a6c206ee');
        });
        Schema::create('tl_keyboard_button_keyboard_button', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_f2db72974cd8e4a5de973d33');
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c5e6da4a2f1784a203f6a323');
            $table->index('account_id', 'ix_c45b7628e50530b73854f142');
        });
        Schema::create('tl_keyboard_button_keyboard_button_buy', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_69b8e52f417589798a61b5f8');
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d7b5817df113ed5bba703342');
            $table->index('account_id', 'ix_2ea9c07b7387bb427cfdbb8f');
        });
        Schema::create('tl_keyboard_button_keyboard_button_callback', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('requires_password')->default(false);
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_36fa54a5413b66f52a41f074');
            $table->text('text')->nullable();
            $table->binary('data')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_897f458441deb02cff7e39c0');
            $table->index('account_id', 'ix_89e5c5bd096a67a5286485db');
        });
        Schema::create('tl_keyboard_button_keyboard_button_copy', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_22bd9b166954ecdbc60df757');
            $table->text('text')->nullable();
            $table->text('copy_text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2b224d42c3acbad164e897ba');
            $table->index('account_id', 'ix_095abe51074db01db7870d93');
        });
        Schema::create('tl_keyboard_button_keyboard_button_game', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_9d6ff7ade9064a0ef4647ad8');
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_87ee3c2be5d18e7d9af7a406');
            $table->index('account_id', 'ix_e2a1e86c7850cc1282c1d3e5');
        });
        Schema::create('tl_keyboard_button_keyboard_button_request_geo_location', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_d4ed90aaf552e9dd06b12222');
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5e71a643231483f69c271de5');
            $table->index('account_id', 'ix_a7422aaab4cf11a7dca931ca');
        });
        Schema::create('tl_keyboard_button_keyboard_button_request_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_d66c8da7efb76f5829e64f0c');
            $table->text('text')->nullable();
            $table->integer('button_id')->nullable();
            $table->bigInteger('peer_type')->nullable();
            $table->index('peer_type', 'ix_16b3defd4db32ad577a15d18');
            $table->integer('max_quantity')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_149483641f134a29ba63c28d');
            $table->index('account_id', 'ix_02db50de0cbf9ef8b3969684');
        });
        Schema::create('tl_keyboard_button_keyboard_button_request_phone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_367c02aa8fed07fad8f8ca82');
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d55d53c9227762836bcc6649');
            $table->index('account_id', 'ix_a3609eab8763789786a11be8');
        });
        Schema::create('tl_keyboard_button_keyboard_button_request_poll', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_d1893fa6135eafa150c1a3b4');
            $table->bigInteger('quiz')->nullable();
            $table->index('quiz', 'ix_5a5e19de1e6051ae4a7bfe72');
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2b522349826e4c46548ce57d');
            $table->index('account_id', 'ix_0601a3fabf1e2c5a0af31466');
        });
        Schema::create('tl_keyboard_button_keyboard_button_simple_web_view', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_e797c146c9f4c1005b5f6297');
            $table->text('text')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_04edb6d8906863f535fdbf05');
            $table->index('account_id', 'ix_9a9e35710887ef3bc64d8246');
        });
        Schema::create('tl_keyboard_button_keyboard_button_switch_inline', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('same_peer')->default(false);
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_cd91cecee03f7e1db21370ba');
            $table->text('text')->nullable();
            $table->text('query')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae8a94ebc0870e15077b5c9b');
            $table->index('account_id', 'ix_5437404c5f621ee3443292dc');
        });
        Schema::create('tl_keyboard_button_keyboard_button_switch_inl_24451aa92e03', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_keyboard_button_keyboard_button_switch_inline', 'id', 'fk_fdbef72c0676991ddebb449b')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8ffc7de80710e9fe5f02');
            $table->index('account_id', 'ix_76aa70da9d88d0169e6c59ef');
        });
        Schema::create('tl_keyboard_button_keyboard_button_url', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_e623d1f4e704441bf310e4a0');
            $table->text('text')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dee3a118bc4a196f3ea04f5f');
            $table->index('account_id', 'ix_666e32aff7135dae03aa415e');
        });
        Schema::create('tl_keyboard_button_keyboard_button_url_auth', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_085ad603b012daf8558afb2e');
            $table->text('text')->nullable();
            $table->text('fwd_text')->nullable();
            $table->text('url')->nullable();
            $table->integer('button_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_89b7b6327abcd7139906e6b1');
            $table->index('account_id', 'ix_9ba76b0b6914c748b040695b');
        });
        Schema::create('tl_keyboard_button_keyboard_button_user_profile', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_e29ba1bdfb4be14ff7f9e488');
            $table->text('text')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_f6e4bad91812b6a4247ec237');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_96f0904316a5d4b96d66c8d2');
            $table->index('account_id', 'ix_a820afd9bbdd7f8380b637ed');
        });
        Schema::create('tl_keyboard_button_keyboard_button_web_view', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('style')->nullable();
            $table->index('style', 'ix_b54b7f96bafc1902fd9bf621');
            $table->text('text')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e4626fb2310c53f45df2bda7');
            $table->index('account_id', 'ix_d145ed72d395c52c01cc253e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_web_view');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_user_profile');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_url_auth');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_url');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_switch_inl_24451aa92e03');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_switch_inline');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_simple_web_view');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_request_poll');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_request_phone');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_request_peer');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_request_geo_location');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_game');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_copy');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_callback');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button_buy');
        Schema::dropIfExists('tl_keyboard_button_keyboard_button');
        Schema::dropIfExists('tl_keyboard_button_input_keyboard_button_user_profile');
        Schema::dropIfExists('tl_keyboard_button_input_keyboard_button_url_auth');
        Schema::dropIfExists('tl_keyboard_button_input_keyboard_button_request_peer');
    }
};
