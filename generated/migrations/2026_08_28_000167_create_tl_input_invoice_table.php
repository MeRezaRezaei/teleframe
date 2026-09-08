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
        Schema::create('tl_input_invoice', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_invoice_input_invoice_business_bot_transfer_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('bot');
            $table->bigInteger('stars');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_chat_invite_subscription', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->text('hash');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('msg_id');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_premium_auth_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('purpose');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_premium_gift_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('purpose');
            $table->uuid('option');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_premium_gift_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('user_id');
            $table->integer('months');
            $table->uuid('message')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_slug', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->text('slug');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('hide_name')->default(false);
            $table->boolean('include_upgrade')->default(false);
            $table->uuid('peer');
            $table->bigInteger('gift_id');
            $table->uuid('message')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_auction_bid', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('hide_name')->default(false);
            $table->boolean('update_bid')->default(false);
            $table->uuid('peer')->nullable();
            $table->bigInteger('gift_id');
            $table->bigInteger('bid_amount');
            $table->uuid('message')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_drop_bd25ad852c6a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('stargift');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_prepaid_upgrade', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('peer');
            $table->text('hash');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_resale', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('ton')->default(false);
            $table->text('slug');
            $table->uuid('to_id');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_transfer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('stargift');
            $table->uuid('to_id');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_upgrade', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('keep_original_details')->default(false);
            $table->uuid('stargift');
            $table->timestamps();
        });
        Schema::create('tl_input_invoice_input_invoice_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('purpose');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_invoice_input_invoice_stars');
        Schema::dropIfExists('tl_input_invoice_input_invoice_star_gift_upgrade');
        Schema::dropIfExists('tl_input_invoice_input_invoice_star_gift_transfer');
        Schema::dropIfExists('tl_input_invoice_input_invoice_star_gift_resale');
        Schema::dropIfExists('tl_input_invoice_input_invoice_star_gift_prepaid_upgrade');
        Schema::dropIfExists('tl_input_invoice_input_invoice_star_gift_drop_bd25ad852c6a');
        Schema::dropIfExists('tl_input_invoice_input_invoice_star_gift_auction_bid');
        Schema::dropIfExists('tl_input_invoice_input_invoice_star_gift');
        Schema::dropIfExists('tl_input_invoice_input_invoice_slug');
        Schema::dropIfExists('tl_input_invoice_input_invoice_premium_gift_stars');
        Schema::dropIfExists('tl_input_invoice_input_invoice_premium_gift_code');
        Schema::dropIfExists('tl_input_invoice_input_invoice_premium_auth_code');
        Schema::dropIfExists('tl_input_invoice_input_invoice_message');
        Schema::dropIfExists('tl_input_invoice_input_invoice_chat_invite_subscription');
        Schema::dropIfExists('tl_input_invoice_input_invoice_business_bot_transfer_stars');
        Schema::dropIfExists('tl_input_invoice');
    }
};
