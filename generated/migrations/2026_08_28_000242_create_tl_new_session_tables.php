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
        Schema::create('tl_new_session_new_session_created', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('first_msg_id')->nullable();
            $table->index('first_msg_id', 'ix_51686589b997bffdb56a261a');
            $table->bigInteger('unique_id')->nullable();
            $table->index('unique_id', 'ix_1d47b8746133c49301e9ada1');
            $table->bigInteger('server_salt')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ea584871e47d7b2d6c4ddd5d');
            $table->index('account_id', 'ix_80a9285b1a36b693a91e4448');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_new_session_new_session_created');
    }
};
