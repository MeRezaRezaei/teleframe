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
        Schema::create('tl_account_resolved_business_chat_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_c591db58a589', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_resolved_business_chat_links')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer');
            $table->text('message');
            $table->timestamps();
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_ee95c75ee22d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_resolved_business_chat_links_resol_c591db58a589')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_a71d0626b79e788e528e');
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_eb9e805b9db6', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_resolved_business_chat_links_resol_c591db58a589')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_55000d74c5592f728dd3');
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_eaf95553a9e4', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_resolved_business_chat_links_resol_c591db58a589')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5547405cf297f9324a1e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_resolved_business_chat_links_resol_eaf95553a9e4');
        Schema::dropIfExists('tl_account_resolved_business_chat_links_resol_eb9e805b9db6');
        Schema::dropIfExists('tl_account_resolved_business_chat_links_resol_ee95c75ee22d');
        Schema::dropIfExists('tl_account_resolved_business_chat_links_resol_c591db58a589');
        Schema::dropIfExists('tl_account_resolved_business_chat_links');
    }
};
