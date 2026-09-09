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
        Schema::create('tl_sponsored_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a5c04d0846b922e277776258');
            $table->index('account_id', 'ix_ef21fac2f45d71526326eeb1');
        });
        Schema::create('tl_sponsored_peer_sponsored_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sponsored_peer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->binary('random_id');
            $table->bigInteger('peer');
            $table->index('peer', 'ix_6e8f437f7bfd598ce1da93e7');
            $table->text('sponsor_info')->nullable();
            $table->text('additional_info')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5faee33186478a33fbfc02e8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sponsored_peer_sponsored_peer');
        Schema::dropIfExists('tl_sponsored_peer');
    }
};
