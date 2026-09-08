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
        Schema::create('tl_stars_transaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_stars_transaction_stars_transaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('refund')->default(false);
            $table->boolean('pending')->default(false);
            $table->boolean('failed')->default(false);
            $table->boolean('gift')->default(false);
            $table->boolean('reaction')->default(false);
            $table->boolean('stargift_upgrade')->default(false);
            $table->boolean('business_transfer')->default(false);
            $table->boolean('stargift_resale')->default(false);
            $table->boolean('posts_search')->default(false);
            $table->boolean('stargift_prepaid_upgrade')->default(false);
            $table->boolean('stargift_drop_original_details')->default(false);
            $table->boolean('phonegroup_message')->default(false);
            $table->boolean('stargift_auction_bid')->default(false);
            $table->boolean('offer')->default(false);
            $table->text('tl_id');
            $table->uuid('amount');
            $table->integer('date');
            $table->uuid('peer');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->uuid('photo')->nullable();
            $table->integer('transaction_date')->nullable();
            $table->text('transaction_url')->nullable();
            $table->binary('bot_payload')->nullable();
            $table->integer('msg_id')->nullable();
            $table->integer('subscription_period')->nullable();
            $table->integer('giveaway_post_id')->nullable();
            $table->uuid('stargift')->nullable();
            $table->integer('floodskip_number')->nullable();
            $table->integer('starref_commission_permille')->nullable();
            $table->uuid('starref_peer')->nullable();
            $table->uuid('starref_amount')->nullable();
            $table->integer('paid_messages')->nullable();
            $table->integer('premium_gift_months')->nullable();
            $table->integer('ads_proceeds_from_date')->nullable();
            $table->integer('ads_proceeds_to_date')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_stars_transaction_stars_transaction__extended_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stars_transaction_stars_transaction')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_fd17c93f795c689fd985');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_transaction_stars_transaction__extended_media');
        Schema::dropIfExists('tl_stars_transaction_stars_transaction');
        Schema::dropIfExists('tl_stars_transaction');
    }
};
