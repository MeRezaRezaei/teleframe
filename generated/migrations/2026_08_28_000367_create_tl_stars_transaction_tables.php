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
        Schema::create('tl_stars_transaction_stars_transaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
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
            $table->text('tl_id')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->index('amount', 'ix_641fd0824a9a83368a5f98b0');
            $table->integer('date')->nullable();
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_d9179cdad9099ba09f80c22a');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_6b7390f0263aa194c12d22db');
            $table->integer('transaction_date')->nullable();
            $table->text('transaction_url')->nullable();
            $table->binary('bot_payload')->nullable();
            $table->integer('msg_id')->nullable();
            $table->integer('subscription_period')->nullable();
            $table->integer('giveaway_post_id')->nullable();
            $table->bigInteger('stargift')->nullable();
            $table->index('stargift', 'ix_74234cc04d763809abc74350');
            $table->integer('floodskip_number')->nullable();
            $table->integer('starref_commission_permille')->nullable();
            $table->bigInteger('starref_peer')->nullable();
            $table->index('starref_peer', 'ix_b9ef9bca9f4f496ab388da78');
            $table->bigInteger('starref_amount')->nullable();
            $table->index('starref_amount', 'ix_10038ced13ec5e52e8db6f78');
            $table->integer('paid_messages')->nullable();
            $table->integer('premium_gift_months')->nullable();
            $table->integer('ads_proceeds_from_date')->nullable();
            $table->integer('ads_proceeds_to_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_58b6cd453cf8896539115b6f');
            $table->index('account_id', 'ix_3f7153228e1b18817a433bcd');
        });
        Schema::create('tl_stars_transaction_stars_transaction__extended_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c8763db785233b113bcc392c')->references('id')->on('tl_stars_transaction_stars_transaction')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_fd17c93f795c689fd985');
            $table->index('account_id', 'ix_836714268cfea94e1b767713');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_transaction_stars_transaction__extended_media');
        Schema::dropIfExists('tl_stars_transaction_stars_transaction');
    }
};
