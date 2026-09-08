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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_account_saved_ringtone_saved_ringtone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_saved_ringtone')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_account_saved_ringtone_saved_ringtone_converted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_saved_ringtone')->cascadeOnDelete();
            $table->uuid('document');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_saved_ringtone_saved_ringtone_converted');
        Schema::dropIfExists('tl_account_saved_ringtone_saved_ringtone');
        Schema::dropIfExists('tl_account_saved_ringtone');
    }
};
