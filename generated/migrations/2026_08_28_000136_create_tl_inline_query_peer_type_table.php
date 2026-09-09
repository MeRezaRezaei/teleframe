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
        Schema::create('tl_inline_query_peer_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_6ff09050997758aac0250633');
            $table->index('account_id', 'ix_24c4baede4092b04eb46f6cb');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_type_bot_p_m', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_inline_query_peer_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f49652eb5c3cada415b46ada');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_type_broadcast', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_inline_query_peer_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8adf49316fb507cb192c4687');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_type_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_inline_query_peer_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2f6abe348bd1f2d495e970cf');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_type_megagroup', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_inline_query_peer_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7523dc6f00e44325a9c86575');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_type_p_m', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_inline_query_peer_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d5d6fac7f02e53da83f9a3d4');
        });
        Schema::create('tl_inline_query_peer_type_inline_query_peer_t_a7c6c467b6d2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_inline_query_peer_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
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
        Schema::dropIfExists('tl_inline_query_peer_type');
    }
};
