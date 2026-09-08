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
        Schema::create('tl_attach_menu_peer_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_attach_menu_peer_type_attach_menu_peer_type_bot_p_m', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_attach_menu_peer_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_attach_menu_peer_type_attach_menu_peer_type_broadcast', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_attach_menu_peer_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_attach_menu_peer_type_attach_menu_peer_type_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_attach_menu_peer_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_attach_menu_peer_type_attach_menu_peer_type_p_m', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_attach_menu_peer_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_attach_menu_peer_type_attach_menu_peer_typ_43de15eb360c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_attach_menu_peer_type')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_attach_menu_peer_type_attach_menu_peer_typ_43de15eb360c');
        Schema::dropIfExists('tl_attach_menu_peer_type_attach_menu_peer_type_p_m');
        Schema::dropIfExists('tl_attach_menu_peer_type_attach_menu_peer_type_chat');
        Schema::dropIfExists('tl_attach_menu_peer_type_attach_menu_peer_type_broadcast');
        Schema::dropIfExists('tl_attach_menu_peer_type_attach_menu_peer_type_bot_p_m');
        Schema::dropIfExists('tl_attach_menu_peer_type');
    }
};
