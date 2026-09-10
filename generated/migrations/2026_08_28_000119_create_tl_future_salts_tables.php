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
        Schema::create('tl_future_salts_future_salts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('req_msg_id')->nullable();
            $table->index('req_msg_id', 'ix_f45852a380e999bc64c03250');
            $table->integer('now')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a35c44cc8e48da232edae27e');
            $table->index('account_id', 'ix_1b86416ec9c7cdd5b46f3d18');
        });
        Schema::create('tl_future_salts_future_salts__salts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_1323d7299ea2b000104faf98')->references('id')->on('tl_future_salts_future_salts')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ee1429652d938fee9d22');
            $table->index('account_id', 'ix_0ec89e369c8b8d7eb47133a3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_future_salts_future_salts__salts');
        Schema::dropIfExists('tl_future_salts_future_salts');
    }
};
