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
        Schema::create('tl_input_chatlist_input_chatlist_dialog_filter', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('filter_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_aedcb696adcf3841b19be916');
            $table->index('account_id', 'ix_0e58ce4798a8b085e4a5b7a1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_chatlist_input_chatlist_dialog_filter');
    }
};
