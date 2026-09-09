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
        Schema::create('tl_object', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_90aabb3dd3f8c9bb1b441d67');
            $table->index('account_id', 'ix_a6b026b93161a916f0290a97');
        });
        Schema::create('tl_object_gzip_packed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_object')->cascadeOnDelete();
            $table->binary('packed_data');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a40c9a6f69adc6399c33fee2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_object_gzip_packed');
        Schema::dropIfExists('tl_object');
    }
};
