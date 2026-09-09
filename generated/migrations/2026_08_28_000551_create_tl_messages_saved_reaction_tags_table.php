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
        Schema::create('tl_messages_saved_reaction_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_6a3eff6fd01c5a20691a24d4');
            $table->index('account_id', 'ix_3374eb9dbd9db4eb6e3aba26');
        });
        Schema::create('tl_messages_saved_reaction_tags_saved_reaction_tags', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_saved_reaction_tags')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bee13f0d14ff0a0d7a7a6301');
        });
        Schema::create('tl_messages_saved_reaction_tags_saved_reaction_tags__tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_reaction_tags_saved_reaction_tags')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a36934bb9ca98a4c68b3');
            $table->index('account_id', 'ix_0ad3b6587ef83ba682fffd7b');
        });
        Schema::create('tl_messages_saved_reaction_tags_saved_reactio_4b74c8258774', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_saved_reaction_tags')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_70ced957c362ad5d141989db');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_saved_reaction_tags_saved_reactio_4b74c8258774');
        Schema::dropIfExists('tl_messages_saved_reaction_tags_saved_reaction_tags__tags');
        Schema::dropIfExists('tl_messages_saved_reaction_tags_saved_reaction_tags');
        Schema::dropIfExists('tl_messages_saved_reaction_tags');
    }
};
