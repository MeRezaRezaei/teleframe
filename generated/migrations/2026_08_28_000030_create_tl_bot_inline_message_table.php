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
        Schema::create('tl_bot_inline_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_auto', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('invert_media')->default(false);
            $table->text('message');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_medi_0c556e3e04d1', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bot_inline_message_bot_inline_message_media_auto')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_db223efbbc1dfc8f9bf7');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('phone_number');
            $table->text('first_name');
            $table->text('last_name');
            $table->text('vcard');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_geo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('geo');
            $table->integer('heading')->nullable();
            $table->integer('period')->nullable();
            $table->integer('proximity_notification_radius')->nullable();
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_invoice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('shipping_address_requested')->default(false);
            $table->boolean('test')->default(false);
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_venue', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('geo');
            $table->text('title');
            $table->text('address');
            $table->text('provider');
            $table->text('venue_id');
            $table->text('venue_type');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_media_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('invert_media')->default(false);
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('manual')->default(false);
            $table->boolean('safe')->default(false);
            $table->text('message');
            $table->text('url');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_medi_483ead59e63e', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bot_inline_message_bot_inline_message_media_web_page')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f31511e65732f1ca97eb');
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_rich_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('reply_markup')->nullable();
            $table->uuid('rich_message');
            $table->timestamps();
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_text', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('no_webpage')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->text('message');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_bot_inline_message_bot_inline_message_text__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bot_inline_message_bot_inline_message_text')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f1560a04d296f5b8a534');
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
        Schema::dropIfExists('tl_bot_inline_message');
    }
};
