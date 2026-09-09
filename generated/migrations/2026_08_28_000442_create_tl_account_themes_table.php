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
        Schema::create('tl_account_themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1183adc7663e8b1710f6b47b');
            $table->index('account_id', 'ix_05b0ff565e8c249da2ad32e9');
        });
        Schema::create('tl_account_themes_themes', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_themes')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b194df13eb2e94e6dacee4ef');
        });
        Schema::create('tl_account_themes_themes__themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_themes_themes')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_043ed26f3f28110b8bf3');
            $table->index('account_id', 'ix_0e44780e2a8877f1882554c4');
        });
        Schema::create('tl_account_themes_themes_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_themes')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_58aaf8e33499e7a15b9f8548');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_themes_themes_not_modified');
        Schema::dropIfExists('tl_account_themes_themes__themes');
        Schema::dropIfExists('tl_account_themes_themes');
        Schema::dropIfExists('tl_account_themes');
    }
};
