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
        Schema::create('tl_auth_authorization_authorization', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('setup_password_required')->default(false);
            $table->integer('otherwise_relogin_days')->nullable();
            $table->integer('tmp_sessions')->nullable();
            $table->binary('future_auth_token')->nullable();
            $table->bigInteger('tl_user')->nullable();
            $table->index('tl_user', 'ix_408daabd7fdc868febd8f6b3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4f33cd53a94330a96c0a76ea');
            $table->index('account_id', 'ix_3ee35cbbdb23783ce69c73dc');
        });
        Schema::create('tl_auth_authorization_authorization_sign_up_required', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('terms_of_service')->nullable();
            $table->index('terms_of_service', 'ix_e50a07ffea8775c63c4b0db9');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_25adc3afd40e822f9800d0fe');
            $table->index('account_id', 'ix_230851d74228afa475a073b0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_authorization_authorization_sign_up_required');
        Schema::dropIfExists('tl_auth_authorization_authorization');
    }
};
