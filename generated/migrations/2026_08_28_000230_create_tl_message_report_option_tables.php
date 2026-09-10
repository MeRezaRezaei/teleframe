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
        Schema::create('tl_message_report_option_message_report_option', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('text')->nullable();
            $table->binary('option')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3a4f4207b604960eb443b1fd');
            $table->index('account_id', 'ix_b79f4af1b50c3272ff426d96');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_report_option_message_report_option');
    }
};
