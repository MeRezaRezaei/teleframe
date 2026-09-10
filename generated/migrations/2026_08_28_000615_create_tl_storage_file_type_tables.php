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
        Schema::create('tl_storage_file_type_file_gif', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a41d6f891cfe134d5e40808f');
            $table->index('account_id', 'ix_463e98712146eb13a0e5831d');
        });
        Schema::create('tl_storage_file_type_file_jpeg', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ebdfde9d0755196131dd58fe');
            $table->index('account_id', 'ix_a939c49cb6b64d3941cd0fff');
        });
        Schema::create('tl_storage_file_type_file_mov', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_40a237ecee819b7a1fb6de9f');
            $table->index('account_id', 'ix_022db915bc8361ca30efb1ff');
        });
        Schema::create('tl_storage_file_type_file_mp3', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1b317fd99da3dcdbd66ed559');
            $table->index('account_id', 'ix_c3cbb2aa51ba7b1c27c025a9');
        });
        Schema::create('tl_storage_file_type_file_mp4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_94758a64851b33b5010718d8');
            $table->index('account_id', 'ix_13608cf6b4cad69fef9881a5');
        });
        Schema::create('tl_storage_file_type_file_partial', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a0d05def94d92180e7e1f81f');
            $table->index('account_id', 'ix_95e98a815aa3664ed5fa5edd');
        });
        Schema::create('tl_storage_file_type_file_pdf', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8032c45c6a2376979c4264ca');
            $table->index('account_id', 'ix_2d15cfbf9186057d4412c812');
        });
        Schema::create('tl_storage_file_type_file_png', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a6881c9a6f70ad7fecc03663');
            $table->index('account_id', 'ix_19554f21379eca412bd460b8');
        });
        Schema::create('tl_storage_file_type_file_unknown', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e0f5b3d721e593aa5b260111');
            $table->index('account_id', 'ix_42093931392f507d8034226a');
        });
        Schema::create('tl_storage_file_type_file_webp', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_74933eb75265b12e7255bfcd');
            $table->index('account_id', 'ix_e496ed279e58f837becd3da2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_storage_file_type_file_webp');
        Schema::dropIfExists('tl_storage_file_type_file_unknown');
        Schema::dropIfExists('tl_storage_file_type_file_png');
        Schema::dropIfExists('tl_storage_file_type_file_pdf');
        Schema::dropIfExists('tl_storage_file_type_file_partial');
        Schema::dropIfExists('tl_storage_file_type_file_mp4');
        Schema::dropIfExists('tl_storage_file_type_file_mp3');
        Schema::dropIfExists('tl_storage_file_type_file_mov');
        Schema::dropIfExists('tl_storage_file_type_file_jpeg');
        Schema::dropIfExists('tl_storage_file_type_file_gif');
    }
};
