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
        Schema::create('tl_restriction_reason_restriction_reason', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('platform')->nullable();
            $table->text('reason')->nullable();
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b710cd85aaf301e47ac28a16');
            $table->index('account_id', 'ix_13644b2821a33372fb20e156');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_restriction_reason_restriction_reason');
    }
};
