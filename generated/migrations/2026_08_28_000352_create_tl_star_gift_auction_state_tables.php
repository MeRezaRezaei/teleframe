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
        Schema::create('tl_star_gift_auction_state_star_gift_auction_state', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('version')->nullable();
            $table->integer('start_date')->nullable();
            $table->integer('end_date')->nullable();
            $table->bigInteger('min_bid_amount')->nullable();
            $table->integer('next_round_at')->nullable();
            $table->integer('last_gift_num')->nullable();
            $table->integer('gifts_left')->nullable();
            $table->integer('current_round')->nullable();
            $table->integer('total_rounds')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a17509baadf3f184d4d4704d');
            $table->index('account_id', 'ix_152fa177a9ca00a5c5ae1870');
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction__bb6c7ebe1d9b', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_5287408118fba9d20435143d')->references('id')->on('tl_star_gift_auction_state_star_gift_auction_state')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_cc0f2fe61df4f86afd2a');
            $table->index('account_id', 'ix_d80eeff51f4f0d64b6176a3a');
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction__3f263c3c4430', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_810dd8a94fa8e4c20b5de638')->references('id')->on('tl_star_gift_auction_state_star_gift_auction_state')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_81e93d2b65274e46bc49');
            $table->index('account_id', 'ix_c23cad57cee1193bd8eb394a');
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction_state__rounds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_e81916d876bcf17f9a039cd6')->references('id')->on('tl_star_gift_auction_state_star_gift_auction_state')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_42ca4b4f2af788b93303');
            $table->index('account_id', 'ix_403f17c919cb1f965ac0ef45');
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction__3ffddf14cd70', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('start_date')->nullable();
            $table->integer('end_date')->nullable();
            $table->bigInteger('average_price')->nullable();
            $table->integer('listed_count')->nullable();
            $table->integer('fragment_listed_count')->nullable();
            $table->text('fragment_listed_url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2e4199ef15d7d7a17509a5dd');
            $table->index('account_id', 'ix_32e8e1791d128694bdf9658c');
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction__ba2b64ef8cf9', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e284faa47b83ceca65eff252');
            $table->index('account_id', 'ix_a16aac1e00b081f2244753e8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction__ba2b64ef8cf9');
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction__3ffddf14cd70');
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction_state__rounds');
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction__3f263c3c4430');
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction__bb6c7ebe1d9b');
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction_state');
    }
};
