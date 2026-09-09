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
        Schema::create('tl_auth_code_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ac1ff17e604b55e3e16a8d21');
            $table->index('account_id', 'ix_8e8b134bf9d93f8e973d1fb3');
        });
        Schema::create('tl_auth_code_type_code_type_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_code_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f100afba528f10a9d30dc559');
        });
        Schema::create('tl_auth_code_type_code_type_flash_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_code_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_83dd3877ad1d310771750bf9');
        });
        Schema::create('tl_auth_code_type_code_type_fragment_sms', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_code_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f70c5dd74eee95fab5efb7ea');
        });
        Schema::create('tl_auth_code_type_code_type_missed_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_code_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fb71745d54c6e935a33a1310');
        });
        Schema::create('tl_auth_code_type_code_type_sms', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_code_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_365c8e94746f97e4c00df79f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_code_type_code_type_sms');
        Schema::dropIfExists('tl_auth_code_type_code_type_missed_call');
        Schema::dropIfExists('tl_auth_code_type_code_type_fragment_sms');
        Schema::dropIfExists('tl_auth_code_type_code_type_flash_call');
        Schema::dropIfExists('tl_auth_code_type_code_type_call');
        Schema::dropIfExists('tl_auth_code_type');
    }
};
