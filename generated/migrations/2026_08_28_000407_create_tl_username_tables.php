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
        Schema::create('tl_username_username', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('editable')->default(false);
            $table->boolean('active')->default(false);
            $table->text('username')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b4d9bef083a96d3089507bcc');
            $table->index('account_id', 'ix_bc78fdfe4a7cbe0b63f908ef');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_username_username');
    }
};
