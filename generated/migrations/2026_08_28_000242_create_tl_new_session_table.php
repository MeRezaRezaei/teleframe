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
        Schema::create('tl_new_session', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_771a2de0a441a2f7562bf323');
            $table->index('account_id', 'ix_7bfb16de2126d8e0a02a9b0c');
        });
        Schema::create('tl_new_session_new_session_created', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_new_session')->cascadeOnDelete();
            $table->bigInteger('first_msg_id');
            $table->index('first_msg_id', 'ix_51686589b997bffdb56a261a');
            $table->bigInteger('unique_id');
            $table->index('unique_id', 'ix_1d47b8746133c49301e9ada1');
            $table->bigInteger('server_salt');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_80a9285b1a36b693a91e4448');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_new_session_new_session_created');
        Schema::dropIfExists('tl_new_session');
    }
};
