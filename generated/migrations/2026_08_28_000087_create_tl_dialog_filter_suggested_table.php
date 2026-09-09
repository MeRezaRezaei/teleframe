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
        Schema::create('tl_dialog_filter_suggested', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_003615f3b1742bfd39df855d');
            $table->index('account_id', 'ix_50c3009b668c1b6233df4059');
        });
        Schema::create('tl_dialog_filter_suggested_dialog_filter_suggested', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_dialog_filter_suggested')->cascadeOnDelete();
            $table->uuid('filter');
            $table->index('filter', 'ix_250d125da1672ab455d1ffe2');
            $table->text('description');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ffe7030ec95b36c8ab7a5e8e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_dialog_filter_suggested_dialog_filter_suggested');
        Schema::dropIfExists('tl_dialog_filter_suggested');
    }
};
