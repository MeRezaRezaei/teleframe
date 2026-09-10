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
        Schema::create('tl_account_paid_messages_revenue_paid_messages_revenue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('stars_amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_17277b492cbe82e6b6a224f4');
            $table->index('account_id', 'ix_9ac1dc40728cc8b16924f7ce');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_paid_messages_revenue_paid_messages_revenue');
    }
};
