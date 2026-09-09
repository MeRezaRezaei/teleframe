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
        Schema::create('tl_folder', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4431db0fdc9aa9b21b6202c1');
            $table->index('account_id', 'ix_8b266de1547abcbb1a137fb9');
        });
        Schema::create('tl_folder_folder', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_folder')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('autofill_new_broadcasts')->default(false);
            $table->boolean('autofill_public_groups')->default(false);
            $table->boolean('autofill_new_correspondents')->default(false);
            $table->integer('tl_id');
            $table->text('title');
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_51c5fcaffeaeee308d13a9e3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_84620fc0b5021cde5f1e2dfc');
            $table->unique(['account_id', 'tl_id'], 'ux_105ecc6344e8b65ce373');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_folder_folder');
        Schema::dropIfExists('tl_folder');
    }
};
