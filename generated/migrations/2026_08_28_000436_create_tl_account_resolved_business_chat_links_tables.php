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
        Schema::create('tl_account_resolved_business_chat_links_resol_c591db58a589', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_dcfb22e87ba46a937c385bb1');
            $table->text('message')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_69e06994fd062778be869f5d');
            $table->index('account_id', 'ix_53a59edfa8312856e4b77d2d');
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_ee95c75ee22d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_resolved_business_chat_links_resol_c591db58a589', 'id', 'fk_b6866a282e7b0d51c4ca1568')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a71d0626b79e788e528e');
            $table->index('account_id', 'ix_7947867dab3e7b7b738d9e5c');
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_eb9e805b9db6', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_resolved_business_chat_links_resol_c591db58a589', 'id', 'fk_fb7ebff35b11fa7e7889ec8f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_55000d74c5592f728dd3');
            $table->index('account_id', 'ix_49b526ea1565964a3202c746');
        });
        Schema::create('tl_account_resolved_business_chat_links_resol_eaf95553a9e4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_resolved_business_chat_links_resol_c591db58a589', 'id', 'fk_96ffc92bd418d2baf8e4220a')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
