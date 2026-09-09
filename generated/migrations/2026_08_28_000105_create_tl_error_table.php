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
        Schema::create('tl_error', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1c82033c7e55910579b85fce');
            $table->index('account_id', 'ix_f4deb70d527708b644f902ed');
        });
        Schema::create('tl_error_error', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_error')->cascadeOnDelete();
            $table->integer('code');
            $table->text('text');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_90ced86a8c30870098e1d6a7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_error_error');
        Schema::dropIfExists('tl_error');
    }
};
