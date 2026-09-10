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
        Schema::create('tl_payments_exported_invoice_exported_invoice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_22a1d7269e301bdc754e7157');
            $table->index('account_id', 'ix_68a1baf34c671c48bc08d55e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_exported_invoice_exported_invoice');
    }
};
