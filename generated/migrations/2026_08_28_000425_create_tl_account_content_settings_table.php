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
        Schema::create('tl_account_content_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_0f8c95461157f85f247d7376');
            $table->index('account_id', 'ix_ef039bcef47eb3c82a151441');
        });
        Schema::create('tl_account_content_settings_content_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_content_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('sensitive_enabled')->default(false);
            $table->boolean('sensitive_can_change')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c6eb7b5391bb77c04af64ff1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_content_settings_content_settings');
        Schema::dropIfExists('tl_account_content_settings');
    }
};
