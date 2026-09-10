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
        Schema::create('tl_contacts_sponsored_peers_sponsored_peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_80875627cb0131fbe58984b5');
            $table->index('account_id', 'ix_6e4c2a02e29cfcaa0f9dd73a');
        });
        Schema::create('tl_contacts_sponsored_peers_sponsored_peers__peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_sponsored_peers_sponsored_peers', 'id', 'fk_4be1ba184f91b13166716c3a')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_976a388a624b531b8bf6');
            $table->index('account_id', 'ix_19d5147efd2589db30e8065a');
        });
        Schema::create('tl_contacts_sponsored_peers_sponsored_peers__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_sponsored_peers_sponsored_peers', 'id', 'fk_704fe1b11d51a49106a07435')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6d9e67583c45339ff503');
            $table->index('account_id', 'ix_996ee44ffd1a1d1ea0385c3b');
        });
        Schema::create('tl_contacts_sponsored_peers_sponsored_peers__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_contacts_sponsored_peers_sponsored_peers', 'id', 'fk_df5e13a053623592b974b27e')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d1ffee9ca37dba01aeca');
            $table->index('account_id', 'ix_c96ba87e69646b0ea1b4ca2d');
        });
        Schema::create('tl_contacts_sponsored_peers_sponsored_peers_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ce7a054775927c037b88a2b0');
            $table->index('account_id', 'ix_fa3617452a7d28d877f4e410');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_sponsored_peers_sponsored_peers_empty');
        Schema::dropIfExists('tl_contacts_sponsored_peers_sponsored_peers__users');
        Schema::dropIfExists('tl_contacts_sponsored_peers_sponsored_peers__chats');
        Schema::dropIfExists('tl_contacts_sponsored_peers_sponsored_peers__peers');
        Schema::dropIfExists('tl_contacts_sponsored_peers_sponsored_peers');
    }
};
