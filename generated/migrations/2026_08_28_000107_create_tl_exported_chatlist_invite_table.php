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
        Schema::create('tl_exported_chatlist_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_dd45286770a2d33971b69a9d');
            $table->index('account_id', 'ix_926079519cac2d6cb5a82904');
        });
        Schema::create('tl_exported_chatlist_invite_exported_chatlist_invite', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_exported_chatlist_invite')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('title');
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_068c0562a8ea5e5367517f6b');
        });
        Schema::create('tl_exported_chatlist_invite_exported_chatlist_fe83e2c1c582', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_exported_chatlist_invite_exported_chatlist_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_17f1fa26b9f658eab0c3');
            $table->index('account_id', 'ix_00513d3fee6cd6c68add23af');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_exported_chatlist_invite_exported_chatlist_fe83e2c1c582');
        Schema::dropIfExists('tl_exported_chatlist_invite_exported_chatlist_invite');
        Schema::dropIfExists('tl_exported_chatlist_invite');
    }
};
