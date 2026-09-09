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
        Schema::create('tl_auth_sent_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_83db53b1f5598d6fb14b3cb8');
            $table->index('account_id', 'ix_8446e246361c7d6c1a32d1a7');
        });
        Schema::create('tl_auth_sent_code_sent_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('tl_type');
            $table->index('tl_type', 'ix_5221ce07ff136b042290e3e4');
            $table->text('phone_code_hash');
            $table->uuid('next_type')->nullable();
            $table->index('next_type', 'ix_043921360c449dae6717cd9e');
            $table->integer('timeout')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e42a6a88ec4c6da364b4b403');
        });
        Schema::create('tl_auth_sent_code_sent_code_payment_required', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code')->cascadeOnDelete();
            $table->text('store_product');
            $table->text('phone_code_hash');
            $table->text('support_email_address');
            $table->text('support_email_subject');
            $table->integer('premium_days');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e9061b3d7767a7c45bdb383d');
        });
        Schema::create('tl_auth_sent_code_sent_code_success', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code')->cascadeOnDelete();
            $table->uuid('tl_authorization');
            $table->index('tl_authorization', 'ix_932150b4db7548c22141c218');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4a8246ecfe194763072d4d8e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_sent_code_sent_code_success');
        Schema::dropIfExists('tl_auth_sent_code_sent_code_payment_required');
        Schema::dropIfExists('tl_auth_sent_code_sent_code');
        Schema::dropIfExists('tl_auth_sent_code');
    }
};
