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
        Schema::create('tl_phone_call_protocol', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_phone_call_protocol_phone_call_protocol', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call_protocol')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('udp_p2p')->default(false);
            $table->boolean('udp_reflector')->default(false);
            $table->integer('min_layer');
            $table->integer('max_layer');
            $table->timestamps();
        });
        Schema::create('tl_phone_call_protocol_phone_call_protocol__l_d2c022a39003', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_phone_call_protocol_phone_call_protocol')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_32aee1f677188b424135');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_call_protocol_phone_call_protocol__l_d2c022a39003');
        Schema::dropIfExists('tl_phone_call_protocol_phone_call_protocol');
        Schema::dropIfExists('tl_phone_call_protocol');
    }
};
