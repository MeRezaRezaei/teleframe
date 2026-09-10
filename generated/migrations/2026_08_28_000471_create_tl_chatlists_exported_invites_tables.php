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
        Schema::create('tl_chatlists_exported_invites_exported_invites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d132f25e3fe0dc986cf9324f');
            $table->index('account_id', 'ix_d86fdbcbf1146c0dc13583aa');
        });
        Schema::create('tl_chatlists_exported_invites_exported_invites__invites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_chatlists_exported_invites_exported_invites', 'id', 'fk_350728a4bbb6a536544f8971')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6438ea96ecc64c1ea5cb');
            $table->index('account_id', 'ix_532cb1b5ace9923d02c3e2d5');
        });
        Schema::create('tl_chatlists_exported_invites_exported_invites__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_chatlists_exported_invites_exported_invites', 'id', 'fk_eebd0c63c1cbeaf160f30379')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_daaa972bbefb017abf7a');
            $table->index('account_id', 'ix_b0782a09b7f925b5b2008231');
        });
        Schema::create('tl_chatlists_exported_invites_exported_invites__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_chatlists_exported_invites_exported_invites', 'id', 'fk_59e72b91bc8c22fa5cfc3f14')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
