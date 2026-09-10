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
        Schema::create('tl_bind_auth_key_inner_bind_auth_key_inner', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('nonce')->nullable();
            $table->bigInteger('temp_auth_key_id')->nullable();
            $table->index('temp_auth_key_id', 'ix_510f10d7821c60301c257437');
            $table->bigInteger('perm_auth_key_id')->nullable();
            $table->index('perm_auth_key_id', 'ix_4452b975a31f31decfeb71ae');
            $table->bigInteger('temp_session_id')->nullable();
            $table->index('temp_session_id', 'ix_ffe1b6de8fedf5d4864173da');
            $table->integer('expires_at')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_68e5288657a83b9162447cc2');
            $table->index('account_id', 'ix_1612df208560a48ea5f02a91');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bind_auth_key_inner_bind_auth_key_inner');
    }
};
