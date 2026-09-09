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
            $table->index('constructor_id', 'ix_20cfcb1301b6d0b441330df5');
            $table->index('account_id', 'ix_e7212be3377fb1706651c70d');
        });
        Schema::create('tl_auth_authorization_authorization', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_authorization')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('setup_password_required')->default(false);
            $table->integer('otherwise_relogin_days')->nullable();
            $table->integer('tmp_sessions')->nullable();
            $table->binary('future_auth_token')->nullable();
            $table->uuid('tl_user');
            $table->index('tl_user', 'ix_408daabd7fdc868febd8f6b3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3ee35cbbdb23783ce69c73dc');
        });
        Schema::create('tl_auth_authorization_authorization_sign_up_required', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_authorization')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('terms_of_service')->nullable();
            $table->index('terms_of_service', 'ix_e50a07ffea8775c63c4b0db9');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_230851d74228afa475a073b0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_authorization_authorization_sign_up_required');
        Schema::dropIfExists('tl_auth_authorization_authorization');
        Schema::dropIfExists('tl_auth_authorization');
    }
};
