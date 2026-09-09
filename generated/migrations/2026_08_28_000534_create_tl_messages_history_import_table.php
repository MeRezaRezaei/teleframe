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
        Schema::create('tl_messages_history_import', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_acc495b59a59b460256ebfb2');
            $table->index('account_id', 'ix_f3b5a7d1a9b0dd261a021b38');
        });
        Schema::create('tl_messages_history_import_history_import', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_history_import')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5677e7a4db1b27f3444ab7b0');
            $table->unique(['account_id', 'tl_id'], 'ux_e096a9498a9dd30398a8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_history_import_history_import');
        Schema::dropIfExists('tl_messages_history_import');
    }
};
