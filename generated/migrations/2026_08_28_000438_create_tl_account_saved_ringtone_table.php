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
        Schema::create('tl_account_saved_ringtone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1762c4d1e97a3f7e8dad9c62');
            $table->index('account_id', 'ix_0dfe9bfede767f3156c602b6');
        });
        Schema::create('tl_account_saved_ringtone_saved_ringtone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_saved_ringtone')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d8d71019ef1acc49c0e8b31e');
        });
        Schema::create('tl_account_saved_ringtone_saved_ringtone_converted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_saved_ringtone')->cascadeOnDelete();
            $table->uuid('document');
            $table->index('document', 'ix_78f19e7c001f061b7caac04c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8240b06ae49953d14cd726db');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_saved_ringtone_saved_ringtone_converted');
        Schema::dropIfExists('tl_account_saved_ringtone_saved_ringtone');
        Schema::dropIfExists('tl_account_saved_ringtone');
    }
};
