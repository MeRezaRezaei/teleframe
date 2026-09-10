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
        Schema::create('tl_fact_check_fact_check', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('need_check')->default(false);
            $table->text('country')->nullable();
            $table->bigInteger('text')->nullable();
            $table->index('text', 'ix_9e4f36c231e1c5d89f6bb6f2');
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_61ea85d02f35b48decf0bc03');
            $table->index('account_id', 'ix_df6a4965aa76e3404642c33b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_fact_check_fact_check');
    }
};
