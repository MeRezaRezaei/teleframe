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
        Schema::create('tl_payment_charge', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_249077dfdd260ca1a26033c8');
            $table->index('account_id', 'ix_5f01845c44d9cd2653b76e23');
        });
        Schema::create('tl_payment_charge_payment_charge', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payment_charge')->cascadeOnDelete();
            $table->text('tl_id');
            $table->text('provider_charge_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3fca0aea88afda645d92ed2e');
            $table->unique(['account_id', 'tl_id'], 'ux_562f73826e1b930f3595');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payment_charge_payment_charge');
        Schema::dropIfExists('tl_payment_charge');
    }
};
