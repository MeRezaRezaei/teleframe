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
        Schema::create('tl_data_j_s_o_n_data_j_s_o_n', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('data')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d01f47c59ce7537492be8166');
            $table->index('account_id', 'ix_82cf83d51db6800e9b8acb05');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_data_j_s_o_n_data_j_s_o_n');
    }
};
