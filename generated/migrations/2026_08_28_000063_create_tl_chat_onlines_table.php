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
        Schema::create('tl_chat_onlines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3e4e3f256eccfab86ad60df8');
            $table->index('account_id', 'ix_75c453971f508304cf435b20');
        });
        Schema::create('tl_chat_onlines_chat_onlines', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_onlines')->cascadeOnDelete();
            $table->integer('onlines');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_45d7c36beccfb4bf4637e2e2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_onlines_chat_onlines');
        Schema::dropIfExists('tl_chat_onlines');
    }
};
