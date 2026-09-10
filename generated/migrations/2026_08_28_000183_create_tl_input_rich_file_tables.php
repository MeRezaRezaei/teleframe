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
        Schema::create('tl_input_rich_file_input_rich_file_document', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_id')->nullable();
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_c909476ff93d6076b2260fc3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9cbf19ab0b118ed1b39f44db');
            $table->index('account_id', 'ix_69e9abc28bfaf62aa5b3912c');
        });
        Schema::create('tl_input_rich_file_input_rich_file_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_id')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_3edaba4d0f5423052d85a0b7');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_eca6b584c6a16129119b5a66');
            $table->index('account_id', 'ix_b7bdd7dec24d6f9543f0a8f8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_rich_file_input_rich_file_photo');
        Schema::dropIfExists('tl_input_rich_file_input_rich_file_document');
    }
};
