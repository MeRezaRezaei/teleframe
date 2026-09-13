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
        Schema::create('tf_stars_subscriptions', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->text('id');
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->integer('until_date')->unsigned();
        $table->boolean('canceled')->default(false);
        $table->boolean('can_refulfill')->default(false);
        $table->boolean('missing_balance')->default(false);
        $table->boolean('bot_canceled')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_subscriptions_pricing', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('period')->unsigned();
        $table->bigInteger('amount')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_subscriptions_chat_invite_hash', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('chat_invite_hash');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_subscriptions_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('title');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_subscriptions_photo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('url');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('mime_type');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_subscriptions_photo_attributes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->boolean('mask')->default(false);
        $table->text('alt');
        $table->boolean('round_message')->default(false);
        $table->boolean('supports_streaming')->default(false);
        $table->boolean('nosound')->default(false);
        $table->double('duration');
        $table->integer('preload_prefix_size')->unsigned();
        $table->double('video_start_ts');
        $table->text('video_codec');
        $table->boolean('voice')->default(false);
        $table->text('title');
        $table->text('performer');
        $table->text('waveform');
        $table->text('file_name');
        $table->boolean('free')->default(false);
        $table->boolean('text_color')->default(false);
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_subscriptions_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('n')->unsigned();
        $table->double('x');
        $table->double('y');
        $table->double('zoom');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_subscriptions_invoice_slug', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('invoice_slug');
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_stars_subscriptions');

        Schema::dropIfExists('tf_stars_subscriptions_pricing');

        Schema::dropIfExists('tf_stars_subscriptions_chat_invite_hash');

        Schema::dropIfExists('tf_stars_subscriptions_title');

        Schema::dropIfExists('tf_stars_subscriptions_photo');

        Schema::dropIfExists('tf_stars_subscriptions_photo_attributes');

        Schema::dropIfExists('tf_stars_subscriptions_photo_attributes_mask_coords');

        Schema::dropIfExists('tf_stars_subscriptions_invoice_slug');

        Schema::dropIfExists('tf_stars_subscriptions_invoice_slug');

        Schema::dropIfExists('tf_stars_subscriptions_photo_attributes_mask_coords');

        Schema::dropIfExists('tf_stars_subscriptions_photo_attributes');

        Schema::dropIfExists('tf_stars_subscriptions_photo');

        Schema::dropIfExists('tf_stars_subscriptions_title');

        Schema::dropIfExists('tf_stars_subscriptions_chat_invite_hash');

        Schema::dropIfExists('tf_stars_subscriptions_pricing');

        Schema::dropIfExists('tf_stars_subscriptions');

    }
};
