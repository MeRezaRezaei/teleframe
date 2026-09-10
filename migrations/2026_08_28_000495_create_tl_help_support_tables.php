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
        Schema::create('tl_help_support_support', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('phone_number')->nullable();
            $table->bigInteger('tl_user')->nullable();
            $table->index('tl_user', 'ix_b04ee344a13d90832e8bfe21');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5e43015ccda36476e7d1927b');
            $table->index('account_id', 'ix_9819714553e23b9fa68b0c05');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_support_support');
    }
};
