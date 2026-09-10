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
        Schema::create('tl_auth_logged_out_logged_out', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->binary('future_auth_token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3478a4e802a9e69e1f6e0efa');
            $table->index('account_id', 'ix_3fe518815fc9b66d508aa557');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_logged_out_logged_out');
    }
};
