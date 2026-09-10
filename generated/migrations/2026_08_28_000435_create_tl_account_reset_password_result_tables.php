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
        Schema::create('tl_account_reset_password_result_reset_passwo_b06ef6c44b97', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('retry_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e4e51341b37156f447255e7c');
            $table->index('account_id', 'ix_d201fd0aaabe54b3823ab0dd');
        });
        Schema::create('tl_account_reset_password_result_reset_password_ok', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ce56d50a7b9feb8318c86d6f');
            $table->index('account_id', 'ix_d0d7e16a02ce65c56ea1256a');
        });
        Schema::create('tl_account_reset_password_result_reset_passwo_87f50999585d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6ad5f7298e42ae969a660eec');
            $table->index('account_id', 'ix_f439d256cf0f9253485c9558');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_reset_password_result_reset_passwo_87f50999585d');
        Schema::dropIfExists('tl_account_reset_password_result_reset_password_ok');
        Schema::dropIfExists('tl_account_reset_password_result_reset_passwo_b06ef6c44b97');
    }
};
