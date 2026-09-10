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
        Schema::create('tl_attach_menu_peer_type_attach_menu_peer_type_bot_p_m', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b2e1e0403639445080075f4c');
            $table->index('account_id', 'ix_dd649c6f1eff4043db99685b');
        });
        Schema::create('tl_attach_menu_peer_type_attach_menu_peer_type_broadcast', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_10c637418fbdad22338d0c94');
            $table->index('account_id', 'ix_d7a41f53398ad85c986fcbec');
        });
        Schema::create('tl_attach_menu_peer_type_attach_menu_peer_type_chat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_57aee6f6b63e8125ab5b9e06');
            $table->index('account_id', 'ix_afa759e116067616dc9a1fae');
        });
        Schema::create('tl_attach_menu_peer_type_attach_menu_peer_type_p_m', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e6fb4c4b6611c0b38cfc7e59');
            $table->index('account_id', 'ix_1e17b4070dea18ac1e503c00');
        });
        Schema::create('tl_attach_menu_peer_type_attach_menu_peer_typ_43de15eb360c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_402c9d174770010039765f67');
            $table->index('account_id', 'ix_5d3f24ece8c42e9e6ea9e61d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_attach_menu_peer_type_attach_menu_peer_typ_43de15eb360c');
        Schema::dropIfExists('tl_attach_menu_peer_type_attach_menu_peer_type_p_m');
        Schema::dropIfExists('tl_attach_menu_peer_type_attach_menu_peer_type_chat');
        Schema::dropIfExists('tl_attach_menu_peer_type_attach_menu_peer_type_broadcast');
        Schema::dropIfExists('tl_attach_menu_peer_type_attach_menu_peer_type_bot_p_m');
    }
};
