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
        Schema::create('tl_messages_available_reactions_available_reactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_16a25fd1503ee3770216bd7d');
            $table->index('account_id', 'ix_9bfed757a60639121279aa7f');
        });
        Schema::create('tl_messages_available_reactions_available_rea_505a84565215', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_available_reactions_available_reactions', 'id', 'fk_4c4a16fcbef96353ace0651e')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b5b677d0c3678a6eaab1');
            $table->index('account_id', 'ix_b1dc1926849117814b845c56');
        });
        Schema::create('tl_messages_available_reactions_available_rea_82529c4d65bc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1fd2fc46698e11995fcd6f0b');
            $table->index('account_id', 'ix_c92f3c3d8717111f20a94fde');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_available_reactions_available_rea_82529c4d65bc');
        Schema::dropIfExists('tl_messages_available_reactions_available_rea_505a84565215');
        Schema::dropIfExists('tl_messages_available_reactions_available_reactions');
    }
};
