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
        Schema::create('tl_payment_requested_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_35b11ec97c7914eaac3d6f7a');
            $table->index('account_id', 'ix_7297f9ebb4d87c3e1efb9f10');
        });
        Schema::create('tl_payment_requested_info_payment_requested_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payment_requested_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('name')->nullable();
            $table->text('phone')->nullable();
            $table->text('email')->nullable();
            $table->uuid('shipping_address')->nullable();
            $table->index('shipping_address', 'ix_5f33f5cd97652efb46253485');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b0b98e1fd5156725da4e292e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payment_requested_info_payment_requested_info');
        Schema::dropIfExists('tl_payment_requested_info');
    }
};
