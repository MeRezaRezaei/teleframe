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
        Schema::create('tl_help_support_name', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c8a087aff83a796527a4aed3');
            $table->index('account_id', 'ix_eb0e80d222b15dfc22a395f5');
        });
        Schema::create('tl_help_support_name_support_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_support_name')->cascadeOnDelete();
            $table->text('name');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ea08ed3f087cc5bbe363f282');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_support_name_support_name');
        Schema::dropIfExists('tl_help_support_name');
    }
};
