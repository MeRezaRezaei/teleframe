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
        Schema::create('tl_help_terms_of_service', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_help_terms_of_service_terms_of_service', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_terms_of_service')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('popup')->default(false);
            $table->uuid('tl_id');
            $table->text('text');
            $table->integer('min_age_confirm')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_help_terms_of_service_terms_of_service__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_help_terms_of_service_terms_of_service')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c2e7799c00d66880d9cf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_terms_of_service_terms_of_service__entities');
        Schema::dropIfExists('tl_help_terms_of_service_terms_of_service');
        Schema::dropIfExists('tl_help_terms_of_service');
    }
};
