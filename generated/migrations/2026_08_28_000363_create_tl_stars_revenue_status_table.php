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
        Schema::create('tl_stars_revenue_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c7a1004f788c8766e5344481');
            $table->index('account_id', 'ix_1244105858b9929a9ad883eb');
        });
        Schema::create('tl_stars_revenue_status_stars_revenue_status', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_revenue_status')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('withdrawal_enabled')->default(false);
            $table->uuid('current_balance');
            $table->index('current_balance', 'ix_5e4a17cbca4c1f5b5cae77d9');
            $table->uuid('available_balance');
            $table->index('available_balance', 'ix_1f06ee13f987b89a85d19d81');
            $table->uuid('overall_revenue');
            $table->index('overall_revenue', 'ix_fc0a00b0d25d14e53c36f5c2');
            $table->integer('next_withdrawal_at')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2153ceae1986ad511f917593');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_revenue_status_stars_revenue_status');
        Schema::dropIfExists('tl_stars_revenue_status');
    }
};
