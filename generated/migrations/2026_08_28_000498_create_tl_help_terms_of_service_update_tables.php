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
        Schema::create('tl_help_terms_of_service_update_terms_of_service_update', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('expires')->nullable();
            $table->bigInteger('terms_of_service')->nullable();
            $table->index('terms_of_service', 'ix_365ba3867016d29a640a298d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f3e17427d6b7495d0fe1beb5');
            $table->index('account_id', 'ix_50519137da6f972c1bda5c22');
        });
        Schema::create('tl_help_terms_of_service_update_terms_of_serv_216c987707ad', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('expires')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_05d5f2f5ea726ea0d62e0bc2');
            $table->index('account_id', 'ix_882cb9167280a8f1d64b7757');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_terms_of_service_update_terms_of_serv_216c987707ad');
        Schema::dropIfExists('tl_help_terms_of_service_update_terms_of_service_update');
    }
};
