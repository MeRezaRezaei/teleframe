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
        Schema::create('tl_authorization_authorization', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('tl_current')->default(false);
            $table->boolean('official_app')->default(false);
            $table->boolean('password_pending')->default(false);
            $table->boolean('encrypted_requests_disabled')->default(false);
            $table->boolean('call_requests_disabled')->default(false);
            $table->boolean('unconfirmed')->default(false);
            $table->bigInteger('hash')->nullable();
            $table->text('device_model')->nullable();
            $table->text('platform')->nullable();
            $table->text('system_version')->nullable();
            $table->integer('api_id')->nullable();
            $table->text('app_name')->nullable();
            $table->text('app_version')->nullable();
            $table->integer('date_created')->nullable();
            $table->integer('date_active')->nullable();
            $table->text('ip')->nullable();
            $table->text('country')->nullable();
            $table->text('region')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_acab636d105f68db2817a33b');
            $table->index('account_id', 'ix_0f190b38221273016c7c2305');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_authorization_authorization');
    }
};
