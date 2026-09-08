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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_auth_login_token_login_token', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_login_token')->cascadeOnDelete();
            $table->integer('expires');
            $table->binary('token');
            $table->timestamps();
        });
        Schema::create('tl_auth_login_token_login_token_migrate_to', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_login_token')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->binary('token');
            $table->timestamps();
        });
        Schema::create('tl_auth_login_token_login_token_success', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_login_token')->cascadeOnDelete();
            $table->uuid('tl_authorization');
            $table->timestamps();
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
