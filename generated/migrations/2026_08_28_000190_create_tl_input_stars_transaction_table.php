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
        Schema::create('tl_input_stars_transaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5c8189bd432eceab9ef7dda9');
            $table->index('account_id', 'ix_412381c96f03ee591368355d');
        });
        Schema::create('tl_input_stars_transaction_input_stars_transaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_stars_transaction')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('refund')->default(false);
            $table->text('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ab92cb9fc59246edd56113d5');
            $table->unique(['account_id', 'tl_id'], 'ux_993852539e2eab745dc7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_stars_transaction_input_stars_transaction');
        Schema::dropIfExists('tl_input_stars_transaction');
    }
};
