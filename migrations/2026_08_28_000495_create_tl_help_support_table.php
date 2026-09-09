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
        Schema::create('tl_help_support', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ac14ab11fde3073282b4f2e5');
            $table->index('account_id', 'ix_f8f404088d463c8a1d5d9297');
        });
        Schema::create('tl_help_support_support', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_support')->cascadeOnDelete();
            $table->text('phone_number');
            $table->uuid('tl_user');
            $table->index('tl_user', 'ix_b04ee344a13d90832e8bfe21');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9819714553e23b9fa68b0c05');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_support_support');
        Schema::dropIfExists('tl_help_support');
    }
};
