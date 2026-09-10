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
        Schema::create('tl_messages_available_effects_available_effects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b986129d0af6bb5b3cb7863c');
            $table->index('account_id', 'ix_29850c37abc750622ed225d5');
        });
        Schema::create('tl_messages_available_effects_available_effects__effects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_3df3b7734f5db8a39e98c090')->references('id')->on('tl_messages_available_effects_available_effects')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d028a1705680d693a3ee');
            $table->index('account_id', 'ix_f569d9b61e0c9078206af18c');
        });
        Schema::create('tl_messages_available_effects_available_effects__documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_5956222c77e76c8af80fa84e')->references('id')->on('tl_messages_available_effects_available_effects')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_01a608e9774137eca020');
            $table->index('account_id', 'ix_5d50921c0589c2a4072a5957');
        });
        Schema::create('tl_messages_available_effects_available_effec_9728b73984ee', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f31c59512c594397dcac1bcf');
            $table->index('account_id', 'ix_d13e09324c9f0e389b9fb986');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_available_effects_available_effec_9728b73984ee');
        Schema::dropIfExists('tl_messages_available_effects_available_effects__documents');
        Schema::dropIfExists('tl_messages_available_effects_available_effects__effects');
        Schema::dropIfExists('tl_messages_available_effects_available_effects');
    }
};
