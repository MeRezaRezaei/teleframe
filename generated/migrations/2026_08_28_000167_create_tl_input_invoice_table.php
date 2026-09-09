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
            $table->index('constructor_id', 'ix_0da6e7823366a74faae31324');
            $table->index('account_id', 'ix_92341ef465da6c3ed08fca4b');
        });
        Schema::create('tl_input_invoice_input_invoice_business_bot_transfer_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('bot');
            $table->index('bot', 'ix_ecf346a600a8bb5a72f70063');
            $table->bigInteger('stars');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_874c1d3032cd1e85c44b5df1');
        });
        Schema::create('tl_input_invoice_input_invoice_chat_invite_subscription', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->text('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_92ca20ab8ce1cd683c2ac6d6');
        });
        Schema::create('tl_input_invoice_input_invoice_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_e93f92b6146448a3280b170f');
            $table->integer('msg_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e58c49f5c278d049780628fc');
        });
        Schema::create('tl_input_invoice_input_invoice_premium_auth_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('purpose');
            $table->index('purpose', 'ix_2cf4bccb3c166343d132cadf');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_996021ae7b82899afc089b91');
        });
        Schema::create('tl_input_invoice_input_invoice_premium_gift_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('purpose');
            $table->index('purpose', 'ix_5c484787c7ace4b6146a22ad');
            $table->uuid('option');
            $table->index('option', 'ix_bafa4b127884302ce973e908');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7344f17977117447e2d66517');
        });
        Schema::create('tl_input_invoice_input_invoice_premium_gift_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('user_id');
            $table->index('user_id', 'ix_f4a570e4ef6ef5e4d9dfa7b2');
            $table->integer('months');
            $table->uuid('message')->nullable();
            $table->index('message', 'ix_00129e9c68abb8efa66b33c6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7bb5c4efa92871d073ef6552');
        });
        Schema::create('tl_input_invoice_input_invoice_slug', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->text('slug');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ee2900b1f471398365e17e6f');
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('hide_name')->default(false);
            $table->boolean('include_upgrade')->default(false);
            $table->bigInteger('peer');
            $table->index('peer', 'ix_910621621c9ab062ad7384cb');
            $table->bigInteger('gift_id');
            $table->index('gift_id', 'ix_c48619e5ffdfbfcb4d6efee2');
            $table->uuid('message')->nullable();
            $table->index('message', 'ix_61ce5714812b078137b70a82');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5273f78cdfade3dd0e052365');
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_auction_bid', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('hide_name')->default(false);
            $table->boolean('update_bid')->default(false);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_46fc61100671b69b3cdaf048');
            $table->bigInteger('gift_id');
            $table->index('gift_id', 'ix_62a77f2912e80d5dee97bdd3');
            $table->bigInteger('bid_amount');
            $table->uuid('message')->nullable();
            $table->index('message', 'ix_f6983b741fda925b3a4f4deb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e456c5c419c192658b8fd4d3');
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_drop_bd25ad852c6a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('stargift');
            $table->index('stargift', 'ix_2632204a16f4c934862be114');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bbcdaf74511a748b35ec56ac');
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_prepaid_upgrade', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_90fb676879538209fb2bc7cd');
            $table->text('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_eb39fb9376807f33c50fd247');
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_resale', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('ton')->default(false);
            $table->text('slug');
            $table->bigInteger('to_id');
            $table->index('to_id', 'ix_2eac14eca0950dd9605e8360');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7324a6acc8f62f6e9a70db84');
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_transfer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('stargift');
            $table->index('stargift', 'ix_c0e6f1a6157ad7ec7ac73e4d');
            $table->bigInteger('to_id');
            $table->index('to_id', 'ix_e223e01814b6dae31b1de398');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1cf0e4498c7489669bf4a8a8');
        });
        Schema::create('tl_input_invoice_input_invoice_star_gift_upgrade', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('keep_original_details')->default(false);
            $table->uuid('stargift');
            $table->index('stargift', 'ix_f51f329943ae8ddd0695fc61');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d54a1c3c2e7a6d6b49fc4054');
        });
        Schema::create('tl_input_invoice_input_invoice_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_invoice')->cascadeOnDelete();
            $table->uuid('purpose');
            $table->index('purpose', 'ix_7d99d689a6855c52c2902204');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cd112129d9aab128f4af6a0d');
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
