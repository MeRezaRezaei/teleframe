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
        Schema::create('tl_bot_inline_message_bot_inline_message_media_auto', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('invert_media')->default(false);
            $table->text('message')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_d72cade53283146a9a02ac2e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c1d7e27fabdad1eb38452c5c');
            $table->index('account_id', 'ix_3b0ee013e9b041bc2dd897d5');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_medi_0c556e3e04d1', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_bot_inline_message_bot_inline_message_media_auto', 'id', 'fk_6654b50172cf464a5b9a56fe')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_db223efbbc1dfc8f9bf7');
            $table->index('account_id', 'ix_7fccca73b1fb38226a50868a');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_contact', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('phone_number')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('vcard')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_20574e8eee0b52ec286731ce');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_00d18691dd9e8a18bc523978');
            $table->index('account_id', 'ix_a3577b1647b5add7815c7ada');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_geo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('geo')->nullable();
            $table->index('geo', 'ix_d500426716e56e07107c28fa');
            $table->integer('heading')->nullable();
            $table->integer('period')->nullable();
            $table->integer('proximity_notification_radius')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_4bae79412aa1bd28c39e7b26');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6679412efaa8c15397bded6c');
            $table->index('account_id', 'ix_d8a94f165cfa5c09de853137');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_invoice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('shipping_address_requested')->default(false);
            $table->boolean('test')->default(false);
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_3454ccbce7bcee0e9cc378f5');
            $table->text('currency')->nullable();
            $table->bigInteger('total_amount')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_b0ad480e2d532da5bca3cef3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6642279628ebd5b90fccfeea');
            $table->index('account_id', 'ix_61a831df30de86ef6c7a95e5');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_venue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('geo')->nullable();
            $table->index('geo', 'ix_c2e3f0dd5a698d7bb66b0ad0');
            $table->text('title')->nullable();
            $table->text('address')->nullable();
            $table->text('provider')->nullable();
            $table->text('venue_id')->nullable();
            $table->text('venue_type')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_d469bb5b6d4086c782a129e0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c6a30c617a8e6c7a1724961d');
            $table->index('account_id', 'ix_18488e279c3c2ead8eb1cccf');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_web_page', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('invert_media')->default(false);
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('manual')->default(false);
            $table->boolean('safe')->default(false);
            $table->text('message')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_80d714b810b703fc8cd08ee0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_beade96afc4689260ff4319a');
            $table->index('account_id', 'ix_0c8791c5794f05b550f07d38');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_medi_483ead59e63e', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_bot_inline_message_bot_inline_message_media_web_page', 'id', 'fk_a6dcc778a3e52c18a9b693b5')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f31511e65732f1ca97eb');
            $table->index('account_id', 'ix_f8ecb2e1a785126128ce3728');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_rich_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_bf12df39f272272a96d64a08');
            $table->bigInteger('rich_message')->nullable();
            $table->index('rich_message', 'ix_ccab67a8bc224e65903c99ff');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_548257368d77829df2f1f51b');
            $table->index('account_id', 'ix_83c79113ab9daa220eb69fa7');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_text', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('no_webpage')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->text('message')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_d805e309c067f20b5985d73c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_46d6853298bc0fc44eb998ab');
            $table->index('account_id', 'ix_0c54345e7756a8d5ac079a6d');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_text__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_bot_inline_message_bot_inline_message_text', 'id', 'fk_d2073ae043e94b9cb4b27740')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f1560a04d296f5b8a534');
            $table->index('account_id', 'ix_fa9a61809934f11188286490');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_text__entities');
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_text');
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_rich_message');
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_medi_483ead59e63e');
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_media_web_page');
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_media_venue');
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_media_invoice');
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_media_geo');
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_media_contact');
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_medi_0c556e3e04d1');
        Schema::dropIfExists('tl_bot_inline_message_bot_inline_message_media_auto');
    }
};
