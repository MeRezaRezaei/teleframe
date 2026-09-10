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
        Schema::create('tl_search_results_position_search_result_position', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('msg_id')->nullable();
            $table->integer('date')->nullable();
            $table->integer('tl_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b14cde2547a44f0812b29e72');
            $table->index('account_id', 'ix_85b686a7a32167eadb634dce');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_search_results_position_search_result_position');
    }
};
