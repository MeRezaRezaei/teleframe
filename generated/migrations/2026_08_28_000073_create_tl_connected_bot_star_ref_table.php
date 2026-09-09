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
        Schema::create('tl_connected_bot_star_ref', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b6e0a331e1ada94a4ca2b3a4');
            $table->index('account_id', 'ix_2b289ba43db1c0f41bc28889');
        });
        Schema::create('tl_connected_bot_star_ref_connected_bot_star_ref', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_connected_bot_star_ref')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('revoked')->default(false);
            $table->text('url');
            $table->integer('date');
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_d0f560cbf470a3cc0aa9e48c');
            $table->integer('commission_permille');
            $table->integer('duration_months')->nullable();
            $table->bigInteger('participants');
            $table->bigInteger('revenue');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_35d39087f01901288121161c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_connected_bot_star_ref_connected_bot_star_ref');
        Schema::dropIfExists('tl_connected_bot_star_ref');
    }
};
