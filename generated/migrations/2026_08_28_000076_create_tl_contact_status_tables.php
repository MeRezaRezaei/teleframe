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
        Schema::create('tl_contact_status_contact_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_43b66d72ca1a08960641dad3');
            $table->bigInteger('status')->nullable();
            $table->index('status', 'ix_9805e238c3ec66430dca1a18');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b6e6918082f98fdd85d9e735');
            $table->index('account_id', 'ix_f28ab6f83e41f38da3f8e4bd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contact_status_contact_status');
    }
};
