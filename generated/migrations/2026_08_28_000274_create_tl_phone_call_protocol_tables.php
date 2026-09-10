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
        Schema::create('tl_phone_call_protocol_phone_call_protocol', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('udp_p2p')->default(false);
            $table->boolean('udp_reflector')->default(false);
            $table->integer('min_layer')->nullable();
            $table->integer('max_layer')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_11e42400f949c8dcdf673ae0');
            $table->index('account_id', 'ix_696f9ee4291f6c7ab4b24803');
        });
        Schema::create('tl_phone_call_protocol_phone_call_protocol__l_d2c022a39003', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_phone_call_protocol_phone_call_protocol', 'id', 'fk_101b3e103e3631e0c67a7f42')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_32aee1f677188b424135');
            $table->index('account_id', 'ix_79bff1d8b33a4e72e49af6be');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_call_protocol_phone_call_protocol__l_d2c022a39003');
        Schema::dropIfExists('tl_phone_call_protocol_phone_call_protocol');
    }
};
