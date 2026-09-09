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
        Schema::create('tl_bool', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_57698cfecc24ad703214b176');
            $table->index('account_id', 'ix_8fb8b32f68c771fde9b3f4df');
        });
        Schema::create('tl_bool_bool_false', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bool')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c6a87c0adeb7922151f979ad');
        });
        Schema::create('tl_bool_bool_true', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bool')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3cd289d08e7260406cfac508');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bool_bool_true');
        Schema::dropIfExists('tl_bool_bool_false');
        Schema::dropIfExists('tl_bool');
    }
};
