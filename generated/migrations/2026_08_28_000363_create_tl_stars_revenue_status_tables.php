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
        Schema::create('tl_stars_revenue_status_stars_revenue_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('withdrawal_enabled')->default(false);
            $table->bigInteger('current_balance')->nullable();
            $table->index('current_balance', 'ix_5e4a17cbca4c1f5b5cae77d9');
            $table->bigInteger('available_balance')->nullable();
            $table->index('available_balance', 'ix_1f06ee13f987b89a85d19d81');
            $table->bigInteger('overall_revenue')->nullable();
            $table->index('overall_revenue', 'ix_fc0a00b0d25d14e53c36f5c2');
            $table->integer('next_withdrawal_at')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_09dbda8d730b29c520d6845a');
            $table->index('account_id', 'ix_2153ceae1986ad511f917593');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_revenue_status_stars_revenue_status');
    }
};
