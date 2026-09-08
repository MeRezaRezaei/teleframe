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
        Schema::create('tl_auth_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_auth_authorization_authorization', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_authorization')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('setup_password_required')->default(false);
            $table->integer('otherwise_relogin_days')->nullable();
            $table->integer('tmp_sessions')->nullable();
            $table->binary('future_auth_token')->nullable();
            $table->uuid('tl_user');
            $table->timestamps();
        });
        Schema::create('tl_auth_authorization_authorization_sign_up_required', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_authorization')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('terms_of_service')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_authorization_authorization_sign_up_required');
        Schema::dropIfExists('tl_auth_authorization_authorization');
        Schema::dropIfExists('tl_auth_authorization');
    }
};
