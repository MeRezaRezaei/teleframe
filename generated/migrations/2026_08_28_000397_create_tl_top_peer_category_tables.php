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
        Schema::create('tl_top_peer_category_top_peer_category_bots_app', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_135a62a25842f0f87531b07f');
            $table->index('account_id', 'ix_cc696556a1815b58e9fb3550');
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_guest_chat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5caf07289bb782a6e0914d44');
            $table->index('account_id', 'ix_92f1f0035626f08ad2d0b5af');
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_inline', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_42695b0f4000d65b569219f7');
            $table->index('account_id', 'ix_c95f52e14b8c8bb3e72e2ac8');
        });
        Schema::create('tl_top_peer_category_top_peer_category_bots_p_m', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_66ea81d5277146c06eb879db');
            $table->index('account_id', 'ix_4e7cca2dbf47edb49bd541cd');
        });
        Schema::create('tl_top_peer_category_top_peer_category_channels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bcb9bc290666f0cb58795da4');
            $table->index('account_id', 'ix_4a6ce708657db22fcc0e61ff');
        });
        Schema::create('tl_top_peer_category_top_peer_category_correspondents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e83acb6606557a561e16e638');
            $table->index('account_id', 'ix_8d477d29fa3339b602ee24f3');
        });
        Schema::create('tl_top_peer_category_top_peer_category_forward_chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_47728478bd1ee22f0394a58e');
            $table->index('account_id', 'ix_f19343109f7db4567df3cf62');
        });
        Schema::create('tl_top_peer_category_top_peer_category_forward_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_af5dc00816bd27136e821e29');
            $table->index('account_id', 'ix_468d4134e4c02287e3665d44');
        });
        Schema::create('tl_top_peer_category_top_peer_category_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d550f41e56dd454ba1acb8a9');
            $table->index('account_id', 'ix_c9a0b4d5e6d883414fa73a1b');
        });
        Schema::create('tl_top_peer_category_top_peer_category_phone_calls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_674063bab942141b3ff54278');
            $table->index('account_id', 'ix_ddb5bfd8a049a5b941373f57');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_phone_calls');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_groups');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_forward_users');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_forward_chats');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_correspondents');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_channels');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_bots_p_m');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_bots_inline');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_bots_guest_chat');
        Schema::dropIfExists('tl_top_peer_category_top_peer_category_bots_app');
    }
};
