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
        Schema::create('tl_stats_u_r_l_stats_u_r_l', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7fa2626710baba92b8346f95');
            $table->index('account_id', 'ix_660089f915fa39f139d7b968');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_u_r_l_stats_u_r_l');
    }
};
