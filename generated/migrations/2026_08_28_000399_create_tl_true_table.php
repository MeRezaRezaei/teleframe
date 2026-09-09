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
        Schema::create('tl_true', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_48050a4316624ce680fbe4d4');
            $table->index('account_id', 'ix_35367259e46a8b0773c9b102');
        });
        Schema::create('tl_true_true', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_true')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_33968f48f61a1f58a8402a2c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_true_true');
        Schema::dropIfExists('tl_true');
    }
};
