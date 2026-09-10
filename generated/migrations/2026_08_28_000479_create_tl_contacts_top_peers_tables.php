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
        Schema::create('tl_contacts_top_peers_top_peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5572e360bd35f691afc3aebe');
            $table->index('account_id', 'ix_d83aa8ff66ecc3126873d1eb');
        });
        Schema::create('tl_contacts_top_peers_top_peers__categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_e1080682596cfcb49da589a5')->references('id')->on('tl_contacts_top_peers_top_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f95b8e3b38d9001e6285');
            $table->index('account_id', 'ix_5e1aa9cc0935334ff153be10');
        });
        Schema::create('tl_contacts_top_peers_top_peers__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_31e503b761253a9b2ff9977f')->references('id')->on('tl_contacts_top_peers_top_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4b81e21996936281668f');
            $table->index('account_id', 'ix_28d3f31a591395b5f33c0d36');
        });
        Schema::create('tl_contacts_top_peers_top_peers__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_4cec3bca050b0b3b66fd16a8')->references('id')->on('tl_contacts_top_peers_top_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9985806507b85ce6dd49');
            $table->index('account_id', 'ix_5c42b76fe4827cd970b86d51');
        });
        Schema::create('tl_contacts_top_peers_top_peers_disabled', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_35c0500adb2a185553fc1f25');
            $table->index('account_id', 'ix_28699ca36a0d9320c0eb0375');
        });
        Schema::create('tl_contacts_top_peers_top_peers_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b34e09d6dc8df794883ec7d4');
            $table->index('account_id', 'ix_cff17b4d8228a955892d2442');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contacts_top_peers_top_peers_not_modified');
        Schema::dropIfExists('tl_contacts_top_peers_top_peers_disabled');
        Schema::dropIfExists('tl_contacts_top_peers_top_peers__users');
        Schema::dropIfExists('tl_contacts_top_peers_top_peers__chats');
        Schema::dropIfExists('tl_contacts_top_peers_top_peers__categories');
        Schema::dropIfExists('tl_contacts_top_peers_top_peers');
    }
};
