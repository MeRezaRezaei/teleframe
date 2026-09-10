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
        Schema::create('tl_account_connected_bots_connected_bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_46503afefd84a849de7459af');
            $table->index('account_id', 'ix_b42eedeccec9ebc67f672ec2');
        });
        Schema::create('tl_account_connected_bots_connected_bots__connected_bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_connected_bots_connected_bots', 'id', 'fk_beecacd7339020b8a66b5f4d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_871789b727e77448c566');
            $table->index('account_id', 'ix_ff8153f2dc36df19ef959716');
        });
        Schema::create('tl_account_connected_bots_connected_bots__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_connected_bots_connected_bots', 'id', 'fk_9359308b4952d1e45ccd9a14')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e1f2c70b5f2895d6698e');
            $table->index('account_id', 'ix_c068c1748d4a0f8f53246165');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_connected_bots_connected_bots__users');
        Schema::dropIfExists('tl_account_connected_bots_connected_bots__connected_bots');
        Schema::dropIfExists('tl_account_connected_bots_connected_bots');
    }
};
