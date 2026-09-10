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
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_a91075de57bb758cd8bc528a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9ae8ed004bc69633f2a95a8e');
            $table->index('account_id', 'ix_b05f482671b16c62a8849bb7');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_a_p_i', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_38c1be87bfdc0583d2978830');
            $table->index('account_id', 'ix_98c6591d8e2b6c862682ab31');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_ads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fce830c935323e1b00f8b053');
            $table->index('account_id', 'ix_47b00397e3496ed64b64e813');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_app_store', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7adb0b4bf0e15d2d6b5a6d0c');
            $table->index('account_id', 'ix_af4d597ba988350fb3474c41');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_fragment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_476cee0182412de586691400');
            $table->index('account_id', 'ix_57b6e4294e5a19e6be1e9b0d');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_p_60215ddb959c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c1c07cc0c33593a978b14333');
            $table->index('account_id', 'ix_ba137d804a762b9496b8cbd4');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_p_812ddc94a9e3', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b19d20ca94b4b06ccfdc4ecf');
            $table->index('account_id', 'ix_db78b38bb4cb70103f7a1197');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_p_dea11222315a', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c76def558142e62897ac78ea');
            $table->index('account_id', 'ix_9597fe34a98654ed29554f0d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_p_dea11222315a');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_p_812ddc94a9e3');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_p_60215ddb959c');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_peer_fragment');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_peer_app_store');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_peer_ads');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_peer_a_p_i');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_peer');
    }
};
