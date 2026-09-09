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
        Schema::create('tl_fact_check', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_62366715f0817d3052d1f6e8');
            $table->index('account_id', 'ix_890524308145b35650f478a3');
        });
        Schema::create('tl_fact_check_fact_check', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_fact_check')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('need_check')->default(false);
            $table->text('country')->nullable();
            $table->uuid('text')->nullable();
            $table->index('text', 'ix_9e4f36c231e1c5d89f6bb6f2');
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_df6a4965aa76e3404642c33b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_fact_check_fact_check');
        Schema::dropIfExists('tl_fact_check');
    }
};
