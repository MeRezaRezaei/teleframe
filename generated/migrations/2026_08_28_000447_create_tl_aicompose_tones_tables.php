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
        Schema::create('tl_aicompose_tones_tones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1eeeaa60df417747d3903459');
            $table->index('account_id', 'ix_cb7631ec012f9b11f88983b1');
        });
        Schema::create('tl_aicompose_tones_tones__tones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_51a9b2bc29b74b0a0c39d73e')->references('id')->on('tl_aicompose_tones_tones')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b0b728ea789831b1d172');
            $table->index('account_id', 'ix_641577c5580e2c850bfec2b3');
        });
        Schema::create('tl_aicompose_tones_tones__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_54938fc648eec1992b9b5306')->references('id')->on('tl_aicompose_tones_tones')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6509e41d03471cf85af3');
            $table->index('account_id', 'ix_b6b5935592b68e193539a8a4');
        });
        Schema::create('tl_aicompose_tones_tones_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fe9549c402a913f637599135');
            $table->index('account_id', 'ix_a4d0deb41fbe475a4571ae81');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_aicompose_tones_tones_not_modified');
        Schema::dropIfExists('tl_aicompose_tones_tones__users');
        Schema::dropIfExists('tl_aicompose_tones_tones__tones');
        Schema::dropIfExists('tl_aicompose_tones_tones');
    }
};
