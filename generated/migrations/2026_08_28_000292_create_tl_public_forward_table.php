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
        Schema::create('tl_public_forward', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bd9f0e42087c432a2bb1047c');
            $table->index('account_id', 'ix_6555786fb522278d11737599');
        });
        Schema::create('tl_public_forward_public_forward_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_public_forward')->cascadeOnDelete();
            $table->uuid('message');
            $table->index('message', 'ix_3996ebc337728d309e7c33aa');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_693fcd3af8cdbc7bc6d11adb');
        });
        Schema::create('tl_public_forward_public_forward_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_public_forward')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_fa3cf92ba9eb94ea9e4b88d8');
            $table->uuid('story');
            $table->index('story', 'ix_cdcba058650ae64875ba8caa');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_df52205d5a60d0873ffec9a9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_public_forward_public_forward_story');
        Schema::dropIfExists('tl_public_forward_public_forward_message');
        Schema::dropIfExists('tl_public_forward');
    }
};
