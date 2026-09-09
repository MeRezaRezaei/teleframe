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
        Schema::create('tl_messages_available_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bfc42f522906af1345b6f8b0');
            $table->index('account_id', 'ix_37de034b3554765766db50dc');
        });
        Schema::create('tl_messages_available_reactions_available_reactions', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_available_reactions')->cascadeOnDelete();
            $table->integer('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9bfed757a60639121279aa7f');
        });
        Schema::create('tl_messages_available_reactions_available_rea_505a84565215', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_available_reactions_available_reactions')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b5b677d0c3678a6eaab1');
            $table->index('account_id', 'ix_b1dc1926849117814b845c56');
        });
        Schema::create('tl_messages_available_reactions_available_rea_82529c4d65bc', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_available_reactions')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c92f3c3d8717111f20a94fde');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_available_reactions_available_rea_82529c4d65bc');
        Schema::dropIfExists('tl_messages_available_reactions_available_rea_505a84565215');
        Schema::dropIfExists('tl_messages_available_reactions_available_reactions');
        Schema::dropIfExists('tl_messages_available_reactions');
    }
};
