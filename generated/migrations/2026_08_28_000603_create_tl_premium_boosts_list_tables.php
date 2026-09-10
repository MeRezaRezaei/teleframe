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
        Schema::create('tl_premium_boosts_list_boosts_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('count')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6c1a944fb05787bfc01db7be');
            $table->index('account_id', 'ix_853a2b3d2c92d7ab2a0225af');
        });
        Schema::create('tl_premium_boosts_list_boosts_list__boosts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_06ad729a8baae3b2e92955ee')->references('id')->on('tl_premium_boosts_list_boosts_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8b3c171ce41af469fba5');
            $table->index('account_id', 'ix_5ea6ec6cd1cb1e9a5b1220f8');
        });
        Schema::create('tl_premium_boosts_list_boosts_list__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_fbae52ccb794d474c55c3283')->references('id')->on('tl_premium_boosts_list_boosts_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bc7f8eab808f1896e4ac');
            $table->index('account_id', 'ix_c38031fe82483e4ef192ec33');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_premium_boosts_list_boosts_list__users');
        Schema::dropIfExists('tl_premium_boosts_list_boosts_list__boosts');
        Schema::dropIfExists('tl_premium_boosts_list_boosts_list');
    }
};
