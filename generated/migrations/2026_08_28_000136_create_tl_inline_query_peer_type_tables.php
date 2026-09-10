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
        Schema::create('tl_inline_query_peer_type_inline_query_peer_type_bot_p_m', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_53742f3a6e9a9a76f990ffc6');
            $table->index('account_id', 'ix_f49652eb5c3cada415b46ada');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_type_broadcast', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_999553da1fe87bc90687f349');
            $table->index('account_id', 'ix_8adf49316fb507cb192c4687');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_type_chat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ada0b18f4d28bf4a977fc1ef');
            $table->index('account_id', 'ix_2f6abe348bd1f2d495e970cf');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_type_megagroup', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fe9a2f817973334abfd86753');
            $table->index('account_id', 'ix_7523dc6f00e44325a9c86575');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_type_p_m', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_972b57600684a85b82f90931');
            $table->index('account_id', 'ix_d5d6fac7f02e53da83f9a3d4');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_t_a7c6c467b6d2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dd56bc1358334894a52a6b4c');
            $table->index('account_id', 'ix_c0f495889410efd8589e3d73');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_inline_query_peer_type_inline_query_peer_t_a7c6c467b6d2');
        Schema::dropIfExists('tl_inline_query_peer_type_inline_query_peer_type_p_m');
        Schema::dropIfExists('tl_inline_query_peer_type_inline_query_peer_type_megagroup');
        Schema::dropIfExists('tl_inline_query_peer_type_inline_query_peer_type_chat');
        Schema::dropIfExists('tl_inline_query_peer_type_inline_query_peer_type_broadcast');
        Schema::dropIfExists('tl_inline_query_peer_type_inline_query_peer_type_bot_p_m');
    }
};
