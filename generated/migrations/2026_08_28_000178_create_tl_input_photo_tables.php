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
        Schema::create('tl_input_photo_input_photo', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->binary('file_reference')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e4d53d45d91ab001df919d7a');
            $table->index('account_id', 'ix_9345b0f204d4c994ff3f7039');
        });
        Schema::create('tl_input_photo_input_photo_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_52a14c253cd746ae11dc8abe');
            $table->index('account_id', 'ix_6cf82f9b6e6dcdafe36fbee3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_photo_input_photo_empty');
        Schema::dropIfExists('tl_input_photo_input_photo');
    }
};
