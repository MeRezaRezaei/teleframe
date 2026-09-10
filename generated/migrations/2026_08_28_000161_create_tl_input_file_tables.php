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
        Schema::create('tl_input_file_input_file', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->integer('parts')->nullable();
            $table->text('name')->nullable();
            $table->text('md5_checksum')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d786650c6c2d22604b37e411');
            $table->index('account_id', 'ix_521aef92444c6f816b7a77df');
        });
        Schema::create('tl_input_file_input_file_big', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->integer('parts')->nullable();
            $table->text('name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0432ebbd6bde688f962759aa');
            $table->index('account_id', 'ix_636059ad0eb489784df43bc9');
        });
        Schema::create('tl_input_file_input_file_story_document', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->index('tl_id', 'ix_bea1f4973daefb8cd88cd2cb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5fef7839eb9bdd2dd0fd0e2b');
            $table->index('account_id', 'ix_e221088918a5da6ddf4b0d1b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_file_input_file_story_document');
        Schema::dropIfExists('tl_input_file_input_file_big');
        Schema::dropIfExists('tl_input_file_input_file');
    }
};
