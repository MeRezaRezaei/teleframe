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
        Schema::create('tl_default_history_t_t_l_default_history_t_t_l', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('period')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_65e34074ae4fb09259a8e137');
            $table->index('account_id', 'ix_7791792ec0589b9f41f23fef');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_default_history_t_t_l_default_history_t_t_l');
    }
};
