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
        Schema::create('tl_web_view_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b042475b3c0e754576b8e4ec');
            $table->index('account_id', 'ix_c9965667d58cc00702107915');
        });
        Schema::create('tl_web_view_result_web_view_result_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_view_result')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('fullsize')->default(false);
            $table->boolean('fullscreen')->default(false);
            $table->boolean('same_origin')->default(false);
            $table->bigInteger('query_id')->nullable();
            $table->index('query_id', 'ix_07e9e236a300d1e94b2c4b54');
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a03e4ada2ca514f55c5676ae');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_view_result_web_view_result_url');
        Schema::dropIfExists('tl_web_view_result');
    }
};
