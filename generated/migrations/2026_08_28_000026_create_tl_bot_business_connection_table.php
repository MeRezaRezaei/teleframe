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
        Schema::create('tl_bot_business_connection', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_31ca041b70a149dc53e26bef');
            $table->index('account_id', 'ix_5f7f677188de7b8afc6eb88f');
        });
        Schema::create('tl_bot_business_connection_bot_business_connection', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_business_connection')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('disabled')->default(false);
            $table->text('connection_id');
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_a05776bf10eba6a733b0ff4f');
            $table->integer('dc_id');
            $table->integer('date');
            $table->uuid('rights')->nullable();
            $table->index('rights', 'ix_af518d8f85c4a7169404a92f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f12b1347715c4602e7a6e184');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_business_connection_bot_business_connection');
        Schema::dropIfExists('tl_bot_business_connection');
    }
};
