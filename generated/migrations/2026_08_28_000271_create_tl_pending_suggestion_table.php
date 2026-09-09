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
        Schema::create('tl_pending_suggestion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_a6472cc565920b48da9c4131');
            $table->index('account_id', 'ix_b947fa227a425f991cf697e5');
        });
        Schema::create('tl_pending_suggestion_pending_suggestion', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_pending_suggestion')->cascadeOnDelete();
            $table->text('suggestion');
            $table->uuid('title');
            $table->index('title', 'ix_02276bcc6f1ac9f148090068');
            $table->uuid('description');
            $table->index('description', 'ix_ec3f4c30bc5eae370a959b1b');
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f8e28cacf69a7d3e852723b2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_pending_suggestion_pending_suggestion');
        Schema::dropIfExists('tl_pending_suggestion');
    }
};
