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
        Schema::create('tl_premium_boosts_status_boosts_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('my_boost')->default(false);
            $table->integer('level')->nullable();
            $table->integer('current_level_boosts')->nullable();
            $table->integer('boosts')->nullable();
            $table->integer('gift_boosts')->nullable();
            $table->integer('next_level_boosts')->nullable();
            $table->bigInteger('premium_audience')->nullable();
            $table->index('premium_audience', 'ix_80e00db67cbfcc86dc48ce17');
            $table->text('boost_url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_703b1e49f4ea1f06573a696d');
            $table->index('account_id', 'ix_74ba8d59b1d8b4bbae9dba96');
        });
        Schema::create('tl_premium_boosts_status_boosts_status__prepaid_giveaways', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_premium_boosts_status_boosts_status', 'id', 'fk_dfdf0f4b76de5d46b0d9929c')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_500401dc2a5e35edae20');
            $table->index('account_id', 'ix_bef5ff77030d95e64fd51491');
        });
        Schema::create('tl_premium_boosts_status_boosts_status__my_boost_slots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_premium_boosts_status_boosts_status', 'id', 'fk_1ee1652dd417a62ced9bb02d')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4bddbb6c1c5bae484f0e');
            $table->index('account_id', 'ix_4c41fcb4f3068caf7a098ff3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_premium_boosts_status_boosts_status__my_boost_slots');
        Schema::dropIfExists('tl_premium_boosts_status_boosts_status__prepaid_giveaways');
        Schema::dropIfExists('tl_premium_boosts_status_boosts_status');
    }
};
