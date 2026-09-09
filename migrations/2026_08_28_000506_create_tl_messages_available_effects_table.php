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
        Schema::create('tl_messages_available_effects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_80b989621c2fb8cb7a19b8d0');
            $table->index('account_id', 'ix_e7d873cc845a10c3c08386ca');
        });
        Schema::create('tl_messages_available_effects_available_effects', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_available_effects')->cascadeOnDelete();
            $table->integer('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_29850c37abc750622ed225d5');
        });
        Schema::create('tl_messages_available_effects_available_effects__effects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_available_effects_available_effects')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d028a1705680d693a3ee');
            $table->index('account_id', 'ix_f569d9b61e0c9078206af18c');
        });
        Schema::create('tl_messages_available_effects_available_effects__documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_available_effects_available_effects')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_01a608e9774137eca020');
            $table->index('account_id', 'ix_5d50921c0589c2a4072a5957');
        });
        Schema::create('tl_messages_available_effects_available_effec_9728b73984ee', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_available_effects')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d13e09324c9f0e389b9fb986');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_available_effects_available_effec_9728b73984ee');
        Schema::dropIfExists('tl_messages_available_effects_available_effects__documents');
        Schema::dropIfExists('tl_messages_available_effects_available_effects__effects');
        Schema::dropIfExists('tl_messages_available_effects_available_effects');
        Schema::dropIfExists('tl_messages_available_effects');
    }
};
