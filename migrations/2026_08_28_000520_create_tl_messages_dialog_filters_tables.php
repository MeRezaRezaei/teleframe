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
        Schema::create('tl_messages_dialog_filters_dialog_filters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('tags_enabled')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1ced4ce331023bff4059e917');
            $table->index('account_id', 'ix_fd74f51630ef4d2290af2c98');
        });
        Schema::create('tl_messages_dialog_filters_dialog_filters__filters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_dialog_filters_dialog_filters', 'id', 'fk_634b7bf85553709053ce56ab')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_258d60b188c602811688');
            $table->index('account_id', 'ix_7e4ef9d1c93ebb40485a3ae7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_dialog_filters_dialog_filters__filters');
        Schema::dropIfExists('tl_messages_dialog_filters_dialog_filters');
    }
};
