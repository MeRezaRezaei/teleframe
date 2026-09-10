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
        Schema::create('tl_dialog_filter_suggested_dialog_filter_suggested', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('filter')->nullable();
            $table->index('filter', 'ix_250d125da1672ab455d1ffe2');
            $table->text('description')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fb82138820d79dc84b824709');
            $table->index('account_id', 'ix_ffe7030ec95b36c8ab7a5e8e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_dialog_filter_suggested_dialog_filter_suggested');
    }
};
