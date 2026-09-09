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
        Schema::create('tl_account_authorizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b672adaa82513f5134621e0a');
            $table->index('account_id', 'ix_875200939ada43985ae9c1a2');
        });
        Schema::create('tl_account_authorizations_authorizations', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_authorizations')->cascadeOnDelete();
            $table->integer('authorization_ttl_days');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_964f138f36b6a26c606a0ce0');
        });
        Schema::create('tl_account_authorizations_authorizations__authorizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_authorizations_authorizations')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1e91179c335a9c0f1447');
            $table->index('account_id', 'ix_a8afa57e5f015a6a3ee6f6f1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_authorizations_authorizations__authorizations');
        Schema::dropIfExists('tl_account_authorizations_authorizations');
        Schema::dropIfExists('tl_account_authorizations');
    }
};
