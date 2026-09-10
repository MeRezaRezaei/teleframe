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
        Schema::create('tl_payments_stars_revenue_withdrawal_url_star_0843bfeba80c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_27ae92184415df617ce3fbf9');
            $table->index('account_id', 'ix_72e73215f851aaaa8211af95');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_stars_revenue_withdrawal_url_star_0843bfeba80c');
    }
};
