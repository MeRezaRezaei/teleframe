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
            $table->index('constructor_id', 'ix_110e3c40af3a289456fbc0b7');
            $table->index('account_id', 'ix_d403b5a1b473910f4b55f2dc');
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_c591db58a589', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_resolved_business_chat_links')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_dcfb22e87ba46a937c385bb1');
            $table->text('message');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_53a59edfa8312856e4b77d2d');
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_ee95c75ee22d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_resolved_business_chat_links_resol_c591db58a589')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a71d0626b79e788e528e');
            $table->index('account_id', 'ix_7947867dab3e7b7b738d9e5c');
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_eb9e805b9db6', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_resolved_business_chat_links_resol_c591db58a589')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_55000d74c5592f728dd3');
            $table->index('account_id', 'ix_49b526ea1565964a3202c746');
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_eaf95553a9e4', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_resolved_business_chat_links_resol_c591db58a589')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5547405cf297f9324a1e');
            $table->index('account_id', 'ix_95b75fb47c81274630838b56');
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
