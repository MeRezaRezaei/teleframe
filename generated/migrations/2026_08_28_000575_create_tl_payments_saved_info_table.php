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
        Schema::create('tl_payments_saved_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5d998fffd49c053434744c48');
            $table->index('account_id', 'ix_afa2d665db55d9c53c9a2dc1');
        });
        Schema::create('tl_payments_saved_info_saved_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_saved_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_saved_credentials')->default(false);
            $table->uuid('saved_info')->nullable();
            $table->index('saved_info', 'ix_3153654203af20c2f3c20e0e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ea436e26c6e99df2c31cab8a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_saved_info_saved_info');
        Schema::dropIfExists('tl_payments_saved_info');
    }
};
