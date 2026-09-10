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
        Schema::create('tl_account_sent_email_code_sent_email_code', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('email_pattern')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d18c7df3825e4f32785bb6cc');
            $table->index('account_id', 'ix_9dd95184b1aac8789ff921d3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_sent_email_code_sent_email_code');
    }
};
