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
        Schema::create('tl_sponsored_message_report_option_sponsored__39be773435f3', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('text')->nullable();
            $table->binary('option')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5415451d2d2e9088dcd0a066');
            $table->index('account_id', 'ix_8fe240bf3c045f0bf3152673');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sponsored_message_report_option_sponsored__39be773435f3');
    }
};
