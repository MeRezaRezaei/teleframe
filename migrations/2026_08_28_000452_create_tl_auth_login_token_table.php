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
        Schema::create('tl_auth_login_token', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8b6902915acee0346b83465b');
            $table->index('account_id', 'ix_292d13dfe10ea8b2c11ec866');
        });
        Schema::create('tl_auth_login_token_login_token', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_login_token')->cascadeOnDelete();
            $table->integer('expires');
            $table->binary('token');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_944ff45866a3ccc3ea92e53a');
        });
        Schema::create('tl_auth_login_token_login_token_migrate_to', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_login_token')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->binary('token');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b641a597ef72e810fe1f8dc8');
        });
        Schema::create('tl_auth_login_token_login_token_success', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_login_token')->cascadeOnDelete();
            $table->uuid('tl_authorization');
            $table->index('tl_authorization', 'ix_ef66808afa047875c852db17');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_289132e67f9d038d8da3453e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_login_token_login_token_success');
        Schema::dropIfExists('tl_auth_login_token_login_token_migrate_to');
        Schema::dropIfExists('tl_auth_login_token_login_token');
        Schema::dropIfExists('tl_auth_login_token');
    }
};
