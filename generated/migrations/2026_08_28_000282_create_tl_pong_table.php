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
        Schema::create('tl_pong', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c4aba497c4742f3d00483216');
            $table->index('account_id', 'ix_59aef33ceba2a9d95b7b1417');
        });
        Schema::create('tl_pong_pong', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_pong')->cascadeOnDelete();
            $table->bigInteger('msg_id');
            $table->index('msg_id', 'ix_afa690829e523801c2953a67');
            $table->bigInteger('ping_id');
            $table->index('ping_id', 'ix_8fee571954b7f592b0430b68');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b8588bdcb4e0032f5d277601');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_pong_pong');
        Schema::dropIfExists('tl_pong');
    }
};
