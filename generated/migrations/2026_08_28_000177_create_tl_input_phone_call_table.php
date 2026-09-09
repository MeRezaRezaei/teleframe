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
        Schema::create('tl_input_phone_call', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_278ea3af4920dfa831853b87');
            $table->index('account_id', 'ix_35df658f7b9ef78fb256de8e');
        });
        Schema::create('tl_input_phone_call_input_phone_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_phone_call')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d508df07039de6a669feeb24');
            $table->unique(['account_id', 'tl_id'], 'ux_b2d56d6f66d8a76098ac');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_phone_call_input_phone_call');
        Schema::dropIfExists('tl_input_phone_call');
    }
};
