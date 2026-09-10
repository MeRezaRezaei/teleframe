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
        Schema::create('tl_help_terms_of_service_terms_of_service', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('popup')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->index('tl_id', 'ix_e71a129b32d37d7d7cfcf430');
            $table->text('text')->nullable();
            $table->integer('min_age_confirm')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_888cc64f8c5f2422d73403cf');
            $table->index('account_id', 'ix_21716b899ad8fede2725f2a1');
        });
        Schema::create('tl_help_terms_of_service_terms_of_service__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_terms_of_service_terms_of_service', 'id', 'fk_8b6b50b1e9c90ee63844fae5')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c2e7799c00d66880d9cf');
            $table->index('account_id', 'ix_9447edbfebee052f6ca6ed50');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_terms_of_service_terms_of_service__entities');
        Schema::dropIfExists('tl_help_terms_of_service_terms_of_service');
    }
};
