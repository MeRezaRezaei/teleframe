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
        Schema::create('tl_help_terms_of_service_update', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_892ed3f654649597029d4022');
            $table->index('account_id', 'ix_ff417f83272f5a2d06d8a165');
        });
        Schema::create('tl_help_terms_of_service_update_terms_of_service_update', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_terms_of_service_update')->cascadeOnDelete();
            $table->integer('expires');
            $table->uuid('terms_of_service');
            $table->index('terms_of_service', 'ix_365ba3867016d29a640a298d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_50519137da6f972c1bda5c22');
        });
        Schema::create('tl_help_terms_of_service_update_terms_of_serv_216c987707ad', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_terms_of_service_update')->cascadeOnDelete();
            $table->integer('expires');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_882cb9167280a8f1d64b7757');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_terms_of_service_update_terms_of_serv_216c987707ad');
        Schema::dropIfExists('tl_help_terms_of_service_update_terms_of_service_update');
        Schema::dropIfExists('tl_help_terms_of_service_update');
    }
};
