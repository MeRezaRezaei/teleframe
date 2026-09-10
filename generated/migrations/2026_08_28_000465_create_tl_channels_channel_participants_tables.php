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
        Schema::create('tl_channels_channel_participants_channel_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_34217873e9d0b89dcb86cedd');
            $table->index('account_id', 'ix_20c7cb64e010ce5a838b92c1');
        });
        Schema::create('tl_channels_channel_participants_channel_part_6b6c9e490b25', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_channels_channel_participants_channel_participants', 'id', 'fk_397d229a99c26b482e8a80ee')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d271735286acefa7fcc0');
            $table->index('account_id', 'ix_078c34b0bcc44b9c1d411af7');
        });
        Schema::create('tl_channels_channel_participants_channel_part_10e2c32cd676', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_channels_channel_participants_channel_participants', 'id', 'fk_d3a631b7e098a8a47a55ccdf')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4a95655594ac623b633e');
            $table->index('account_id', 'ix_1addbf48dfede445b6584da3');
        });
        Schema::create('tl_channels_channel_participants_channel_part_c16b51bee12a', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_channels_channel_participants_channel_participants', 'id', 'fk_e41c3c807d417b61109de703')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bc5ad0d230cd978d0215');
            $table->index('account_id', 'ix_0cb597745143a8557f9315aa');
        });
        Schema::create('tl_channels_channel_participants_channel_part_453012fa781f', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2dbfa3fefb1483f92be566a4');
            $table->index('account_id', 'ix_65a5ea343ed49614ab7bd6b8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channels_channel_participants_channel_part_453012fa781f');
        Schema::dropIfExists('tl_channels_channel_participants_channel_part_c16b51bee12a');
        Schema::dropIfExists('tl_channels_channel_participants_channel_part_10e2c32cd676');
        Schema::dropIfExists('tl_channels_channel_participants_channel_part_6b6c9e490b25');
        Schema::dropIfExists('tl_channels_channel_participants_channel_participants');
    }
};
