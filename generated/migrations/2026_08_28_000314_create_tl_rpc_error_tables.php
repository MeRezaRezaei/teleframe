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
        Schema::create('tl_rpc_error_rpc_error', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5bc540a6a34c4f2850dabc85');
            $table->index('account_id', 'ix_af121f017f023b82c47399a7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_rpc_error_rpc_error');
    }
};
