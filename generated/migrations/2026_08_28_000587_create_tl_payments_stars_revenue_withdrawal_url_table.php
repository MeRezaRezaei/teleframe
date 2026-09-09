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
        Schema::create('tl_payments_stars_revenue_withdrawal_url', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4f175e604f05299dfab5e424');
            $table->index('account_id', 'ix_2fce2f5745e1ddceb3f15bd9');
        });
        Schema::create('tl_payments_stars_revenue_withdrawal_url_star_0843bfeba80c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_stars_revenue_withdrawal_url')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_72e73215f851aaaa8211af95');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_stars_revenue_withdrawal_url_star_0843bfeba80c');
        Schema::dropIfExists('tl_payments_stars_revenue_withdrawal_url');
    }
};
