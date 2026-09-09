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
        Schema::create('tl_stars_transaction_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9fcb19a2a9b8bea79a55f5ad');
            $table->index('account_id', 'ix_d5ae2d4471f6260919608838');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_a91075de57bb758cd8bc528a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b05f482671b16c62a8849bb7');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_a_p_i', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_98c6591d8e2b6c862682ab31');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_ads', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_47b00397e3496ed64b64e813');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_app_store', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_af4d597ba988350fb3474c41');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_fragment', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_57b6e4294e5a19e6be1e9b0d');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_p_60215ddb959c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ba137d804a762b9496b8cbd4');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_p_812ddc94a9e3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_db78b38bb4cb70103f7a1197');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_p_dea11222315a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
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
        Schema::dropIfExists('tl_stars_transaction_peer');
    }
};
