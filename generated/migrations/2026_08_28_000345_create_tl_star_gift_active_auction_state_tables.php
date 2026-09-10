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
        Schema::create('tl_star_gift_active_auction_state_star_gift_a_0f6a2e549dbc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('gift')->nullable();
            $table->index('gift', 'ix_7e4eccc657b4494614542cf6');
            $table->bigInteger('state')->nullable();
            $table->index('state', 'ix_d29bfccac8a9963e95cb8104');
            $table->bigInteger('user_state')->nullable();
            $table->index('user_state', 'ix_6f711cf6f33d1c3082fd6e01');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0271cc16dc2ef8ed79bd02a4');
            $table->index('account_id', 'ix_960e6ec22928670315a5ac6a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_active_auction_state_star_gift_a_0f6a2e549dbc');
    }
};
