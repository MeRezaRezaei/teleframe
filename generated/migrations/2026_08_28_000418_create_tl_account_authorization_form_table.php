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
        Schema::create('tl_account_authorization_form', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_account_authorization_form_authorization_form', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_authorization_form')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('privacy_policy_url')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_account_authorization_form_authorization_f_bfc11bfaf63e', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_authorization_form_authorization_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3ca4c0e049bb0df38920');
        });
        Schema::create('tl_account_authorization_form_authorization_form__values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_authorization_form_authorization_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_995f6ddbd388effedcfa');
        });
        Schema::create('tl_account_authorization_form_authorization_form__errors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_authorization_form_authorization_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ca095263d0acbd9ec3fb');
        });
        Schema::create('tl_account_authorization_form_authorization_form__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_authorization_form_authorization_form')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5a05d5e829b6c4a67f85');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_authorization_form_authorization_form__users');
        Schema::dropIfExists('tl_account_authorization_form_authorization_form__errors');
        Schema::dropIfExists('tl_account_authorization_form_authorization_form__values');
        Schema::dropIfExists('tl_account_authorization_form_authorization_f_bfc11bfaf63e');
        Schema::dropIfExists('tl_account_authorization_form_authorization_form');
        Schema::dropIfExists('tl_account_authorization_form');
    }
};
