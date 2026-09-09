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
        Schema::create('tl_null', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_72287f01f481757a2e091648');
            $table->index('account_id', 'ix_42832e326ce12978f2cd7703');
        });
        Schema::create('tl_null_null', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_null')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a476301174eabde61e30a689');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_null_null');
        Schema::dropIfExists('tl_null');
    }
};
