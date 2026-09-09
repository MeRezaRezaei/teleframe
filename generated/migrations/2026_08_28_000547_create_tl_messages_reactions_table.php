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
        Schema::create('tl_messages_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_009b3e06501cd6916005e2d3');
            $table->index('account_id', 'ix_6b859838d29f19aba30228e4');
        });
        Schema::create('tl_messages_reactions_reactions', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_reactions')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_de64e48335f778e049d68b80');
        });
        Schema::create('tl_messages_reactions_reactions__reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_reactions_reactions')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_340cb4dfc7bf05d30c92');
            $table->index('account_id', 'ix_3856b479e7249559b9718984');
        });
        Schema::create('tl_messages_reactions_reactions_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_reactions')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_133ea8be634c99eebdc23042');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_reactions_reactions_not_modified');
        Schema::dropIfExists('tl_messages_reactions_reactions__reactions');
        Schema::dropIfExists('tl_messages_reactions_reactions');
        Schema::dropIfExists('tl_messages_reactions');
    }
};
