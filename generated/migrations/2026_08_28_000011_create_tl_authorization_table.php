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
        Schema::create('tl_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_authorization_authorization', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_authorization')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('tl_current')->default(false);
            $table->boolean('official_app')->default(false);
            $table->boolean('password_pending')->default(false);
            $table->boolean('encrypted_requests_disabled')->default(false);
            $table->boolean('call_requests_disabled')->default(false);
            $table->boolean('unconfirmed')->default(false);
            $table->bigInteger('hash');
            $table->text('device_model');
            $table->text('platform');
            $table->text('system_version');
            $table->integer('api_id');
            $table->text('app_name');
            $table->text('app_version');
            $table->integer('date_created');
            $table->integer('date_active');
            $table->text('ip');
            $table->text('country');
            $table->text('region');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_authorization_authorization');
        Schema::dropIfExists('tl_authorization');
    }
};
