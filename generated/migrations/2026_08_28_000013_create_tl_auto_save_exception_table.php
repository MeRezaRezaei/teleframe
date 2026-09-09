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
        Schema::create('tl_auto_save_exception', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_d40b349d203a89af052e2656');
            $table->index('account_id', 'ix_d08f1a9a556e2234eed64c5d');
        });
        Schema::create('tl_auto_save_exception_auto_save_exception', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auto_save_exception')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_be07fd4b73c2283e951be324');
            $table->uuid('settings');
            $table->index('settings', 'ix_1e25d44f4b693cfeb0de4319');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c48686cafcb72ca9d37aa44c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auto_save_exception_auto_save_exception');
        Schema::dropIfExists('tl_auto_save_exception');
    }
};
