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
        Schema::create('tl_auth_login_token_login_token', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('expires')->nullable();
            $table->binary('token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ad2472104e3281805417678c');
            $table->index('account_id', 'ix_944ff45866a3ccc3ea92e53a');
        });
        Schema::create('tl_auth_login_token_login_token_migrate_to', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('dc_id')->nullable();
            $table->binary('token')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e8e80e7ea60d11ece417c669');
            $table->index('account_id', 'ix_b641a597ef72e810fe1f8dc8');
        });
        Schema::create('tl_auth_login_token_login_token_success', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_authorization')->nullable();
            $table->index('tl_authorization', 'ix_ef66808afa047875c852db17');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cecdd54706806ad733fbde9f');
            $table->index('account_id', 'ix_289132e67f9d038d8da3453e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_login_token_login_token_success');
        Schema::dropIfExists('tl_auth_login_token_login_token_migrate_to');
        Schema::dropIfExists('tl_auth_login_token_login_token');
    }
};
