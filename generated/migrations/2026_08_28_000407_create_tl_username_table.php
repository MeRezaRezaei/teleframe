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
        Schema::create('tl_username', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_f1671a74fc706739f39f9c20');
            $table->index('account_id', 'ix_3ba929325708bc0aab4b74d7');
        });
        Schema::create('tl_username_username', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_username')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('editable')->default(false);
            $table->boolean('active')->default(false);
            $table->text('username');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bc78fdfe4a7cbe0b63f908ef');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_username_username');
        Schema::dropIfExists('tl_username');
    }
};
