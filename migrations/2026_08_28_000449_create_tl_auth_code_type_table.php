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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_auth_code_type_code_type_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_code_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_auth_code_type_code_type_flash_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_code_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_auth_code_type_code_type_fragment_sms', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_code_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_auth_code_type_code_type_missed_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_code_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_auth_code_type_code_type_sms', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_code_type')->cascadeOnDelete();
            $table->timestamps();
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
