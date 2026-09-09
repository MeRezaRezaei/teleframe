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
        Schema::create('tl_bot_verifier_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2a96ed6c7fa92564bdefd0cf');
            $table->index('account_id', 'ix_4056023ab78bdd4b54c497be');
        });
        Schema::create('tl_bot_verifier_settings_bot_verifier_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_verifier_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_modify_custom_description')->default(false);
            $table->bigInteger('icon');
            $table->text('company');
            $table->text('custom_description')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a5b1faf62a270cadbe1dbff4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_verifier_settings_bot_verifier_settings');
        Schema::dropIfExists('tl_bot_verifier_settings');
    }
};
