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
        Schema::create('tl_phone_call_discard_reason', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_phone_call_discard_reason_phone_call_disca_f54184aa04dd', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call_discard_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_phone_call_discard_reason_phone_call_disca_a0f9fca9390c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call_discard_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_phone_call_discard_reason_phone_call_disca_d9fe56a20c35', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call_discard_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_phone_call_discard_reason_phone_call_disca_622a32ffc276', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call_discard_reason')->cascadeOnDelete();
            $table->text('slug');
            $table->timestamps();
        });
        Schema::create('tl_phone_call_discard_reason_phone_call_disca_73069d1fca2f', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_call_discard_reason')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_call_discard_reason_phone_call_disca_73069d1fca2f');
        Schema::dropIfExists('tl_phone_call_discard_reason_phone_call_disca_622a32ffc276');
        Schema::dropIfExists('tl_phone_call_discard_reason_phone_call_disca_d9fe56a20c35');
        Schema::dropIfExists('tl_phone_call_discard_reason_phone_call_disca_a0f9fca9390c');
        Schema::dropIfExists('tl_phone_call_discard_reason_phone_call_disca_f54184aa04dd');
        Schema::dropIfExists('tl_phone_call_discard_reason');
    }
};
