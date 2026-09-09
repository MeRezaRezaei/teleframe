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
        Schema::create('tl_passkey', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_f1e6f567a4d8a3ed5f76c3a9');
            $table->index('account_id', 'ix_fae69aa9a75d64d23eb7d0d8');
        });
        Schema::create('tl_passkey_passkey', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_passkey')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id');
            $table->text('name');
            $table->integer('date');
            $table->bigInteger('software_emoji_id')->nullable();
            $table->index('software_emoji_id', 'ix_9c5b1adacf769e19957c3fdc');
            $table->integer('last_usage_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_68f780dfeb02b7dd44422932');
            $table->unique(['account_id', 'tl_id'], 'ux_d0887fa4b9cb9e723c10');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_passkey_passkey');
        Schema::dropIfExists('tl_passkey');
    }
};
