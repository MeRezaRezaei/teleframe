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
        Schema::create('tl_chat_reactions_chat_reactions_all', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('allow_custom')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c83b96219eccbea7ec4b9be8');
            $table->index('account_id', 'ix_d24dec023d994dadb14afcc6');
        });
        Schema::create('tl_chat_reactions_chat_reactions_none', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1373ad4d261c61ebf788043b');
            $table->index('account_id', 'ix_20cbd1add07e076e88c25b9f');
        });
        Schema::create('tl_chat_reactions_chat_reactions_some', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_753f962bd84130435924c622');
            $table->index('account_id', 'ix_edc4a4e31534dfea001b7302');
        });
        Schema::create('tl_chat_reactions_chat_reactions_some__reactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_chat_reactions_chat_reactions_some', 'id', 'fk_2a3ae60b4d29bdb62a5c291b')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d58f1a14f24e7b5ac901');
            $table->index('account_id', 'ix_782c8b50624ea66914349b69');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_reactions_chat_reactions_some__reactions');
        Schema::dropIfExists('tl_chat_reactions_chat_reactions_some');
        Schema::dropIfExists('tl_chat_reactions_chat_reactions_none');
        Schema::dropIfExists('tl_chat_reactions_chat_reactions_all');
    }
};
