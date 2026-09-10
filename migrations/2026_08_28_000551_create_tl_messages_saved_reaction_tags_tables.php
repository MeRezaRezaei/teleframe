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
        Schema::create('tl_messages_saved_reaction_tags_saved_reaction_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_355c498684f8219ce42efdfd');
            $table->index('account_id', 'ix_bee13f0d14ff0a0d7a7a6301');
        });
        Schema::create('tl_messages_saved_reaction_tags_saved_reaction_tags__tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_saved_reaction_tags_saved_reaction_tags', 'id', 'fk_af0021956632f5dec766f37d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a36934bb9ca98a4c68b3');
            $table->index('account_id', 'ix_0ad3b6587ef83ba682fffd7b');
        });
        Schema::create('tl_messages_saved_reaction_tags_saved_reactio_4b74c8258774', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ed67d70555d6077f96881202');
            $table->index('account_id', 'ix_70ced957c362ad5d141989db');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_saved_reaction_tags_saved_reactio_4b74c8258774');
        Schema::dropIfExists('tl_messages_saved_reaction_tags_saved_reaction_tags__tags');
        Schema::dropIfExists('tl_messages_saved_reaction_tags_saved_reaction_tags');
    }
};
