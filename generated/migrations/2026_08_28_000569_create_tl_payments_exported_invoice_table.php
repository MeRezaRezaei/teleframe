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
        Schema::create('tl_payments_exported_invoice', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_482588cfc92b3f2084feed8a');
            $table->index('account_id', 'ix_f3ba6b1d9c07e4b50a1216bc');
        });
        Schema::create('tl_payments_exported_invoice_exported_invoice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_exported_invoice')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_68a1baf34c671c48bc08d55e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_exported_invoice_exported_invoice');
        Schema::dropIfExists('tl_payments_exported_invoice');
    }
};
