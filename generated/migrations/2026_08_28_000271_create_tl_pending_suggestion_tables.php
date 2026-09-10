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
        Schema::create('tl_pending_suggestion_pending_suggestion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('suggestion')->nullable();
            $table->bigInteger('title')->nullable();
            $table->index('title', 'ix_02276bcc6f1ac9f148090068');
            $table->bigInteger('description')->nullable();
            $table->index('description', 'ix_ec3f4c30bc5eae370a959b1b');
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_444e72dc9ae0ce3191dd27db');
            $table->index('account_id', 'ix_f8e28cacf69a7d3e852723b2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_pending_suggestion_pending_suggestion');
    }
};
