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
        Schema::create('tl_input_check_password_s_r_p_input_check_password_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3acc3fc16fc414284882043d');
            $table->index('account_id', 'ix_54958746c7cf1dfec483ceb4');
        });
        Schema::create('tl_input_check_password_s_r_p_input_check_password_s_r_p', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('srp_id')->nullable();
            $table->index('srp_id', 'ix_07b0e79624d38b58cdc598e7');
            $table->binary('a')->nullable();
            $table->binary('m1')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_aafd86f36033a6ea4a6dd293');
            $table->index('account_id', 'ix_9f7977bea55bfc4dcfed2879');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_check_password_s_r_p_input_check_password_s_r_p');
        Schema::dropIfExists('tl_input_check_password_s_r_p_input_check_password_empty');
    }
};
