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
        Schema::create('tl_star_gift_active_auction_state', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e9db1eb9c2bafb6fe02e2e0c');
            $table->index('account_id', 'ix_8f35cfadec1e9f30e8e45a43');
        });
        Schema::create('tl_star_gift_active_auction_state_star_gift_a_0f6a2e549dbc', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_active_auction_state')->cascadeOnDelete();
            $table->uuid('gift');
            $table->index('gift', 'ix_7e4eccc657b4494614542cf6');
            $table->uuid('state');
            $table->index('state', 'ix_d29bfccac8a9963e95cb8104');
            $table->uuid('user_state');
            $table->index('user_state', 'ix_6f711cf6f33d1c3082fd6e01');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_960e6ec22928670315a5ac6a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_active_auction_state_star_gift_a_0f6a2e549dbc');
        Schema::dropIfExists('tl_star_gift_active_auction_state');
    }
};
