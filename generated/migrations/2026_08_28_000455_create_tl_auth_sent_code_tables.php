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
        Schema::create('tl_auth_sent_code_sent_code', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_5221ce07ff136b042290e3e4');
            $table->text('phone_code_hash')->nullable();
            $table->bigInteger('next_type')->nullable();
            $table->index('next_type', 'ix_043921360c449dae6717cd9e');
            $table->integer('timeout')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_26432945ab9fb46a70b09f4c');
            $table->index('account_id', 'ix_e42a6a88ec4c6da364b4b403');
        });
        Schema::create('tl_auth_sent_code_sent_code_payment_required', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('store_product')->nullable();
            $table->text('phone_code_hash')->nullable();
            $table->text('support_email_address')->nullable();
            $table->text('support_email_subject')->nullable();
            $table->integer('premium_days')->nullable();
            $table->text('currency')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4391298fcc059c0cca66ccc1');
            $table->index('account_id', 'ix_e9061b3d7767a7c45bdb383d');
        });
        Schema::create('tl_auth_sent_code_sent_code_success', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_authorization')->nullable();
            $table->index('tl_authorization', 'ix_932150b4db7548c22141c218');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8204acf01bacba2c41ee84a5');
            $table->index('account_id', 'ix_4a8246ecfe194763072d4d8e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_sent_code_sent_code_success');
        Schema::dropIfExists('tl_auth_sent_code_sent_code_payment_required');
        Schema::dropIfExists('tl_auth_sent_code_sent_code');
    }
};
