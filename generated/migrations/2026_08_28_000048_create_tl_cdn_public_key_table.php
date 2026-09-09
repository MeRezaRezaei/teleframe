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
        Schema::create('tl_cdn_public_key', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_6e90d999ecaf575106a3a5e3');
            $table->index('account_id', 'ix_956873f3afe144b117d6b8bc');
        });
        Schema::create('tl_cdn_public_key_cdn_public_key', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_cdn_public_key')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->text('public_key');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8bb557900aa421dfeb5d5a7a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_cdn_public_key_cdn_public_key');
        Schema::dropIfExists('tl_cdn_public_key');
    }
};
