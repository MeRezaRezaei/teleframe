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
        Schema::create('tl_chatlists_exported_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_730ee49bd4836737b3e0bfea');
            $table->index('account_id', 'ix_cb77aab780eee2ac3c08b82b');
        });
        Schema::create('tl_chatlists_exported_invites_exported_invites', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chatlists_exported_invites')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d86fdbcbf1146c0dc13583aa');
        });
        Schema::create('tl_chatlists_exported_invites_exported_invites__invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_exported_invites_exported_invites')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6438ea96ecc64c1ea5cb');
            $table->index('account_id', 'ix_532cb1b5ace9923d02c3e2d5');
        });
        Schema::create('tl_chatlists_exported_invites_exported_invites__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_exported_invites_exported_invites')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_daaa972bbefb017abf7a');
            $table->index('account_id', 'ix_b0782a09b7f925b5b2008231');
        });
        Schema::create('tl_chatlists_exported_invites_exported_invites__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_exported_invites_exported_invites')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9d01f08b7f087f66cf20');
            $table->index('account_id', 'ix_57cd2b5ad1c0fb8ca0c469fb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chatlists_exported_invites_exported_invites__users');
        Schema::dropIfExists('tl_chatlists_exported_invites_exported_invites__chats');
        Schema::dropIfExists('tl_chatlists_exported_invites_exported_invites__invites');
        Schema::dropIfExists('tl_chatlists_exported_invites_exported_invites');
        Schema::dropIfExists('tl_chatlists_exported_invites');
    }
};
