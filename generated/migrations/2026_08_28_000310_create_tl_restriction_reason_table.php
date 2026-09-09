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
        Schema::create('tl_restriction_reason', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b486388043f97d3cf9dd0e03');
            $table->index('account_id', 'ix_031b31602c6bfe4826efcd7f');
        });
        Schema::create('tl_restriction_reason_restriction_reason', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_restriction_reason')->cascadeOnDelete();
            $table->text('platform');
            $table->text('reason');
            $table->text('text');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_13644b2821a33372fb20e156');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_restriction_reason_restriction_reason');
        Schema::dropIfExists('tl_restriction_reason');
    }
};
