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
        Schema::create('tl_account_authorizations_authorizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('authorization_ttl_days')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5adac3617ee010d083dbea41');
            $table->index('account_id', 'ix_964f138f36b6a26c606a0ce0');
        });
        Schema::create('tl_account_authorizations_authorizations__authorizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_authorizations_authorizations', 'id', 'fk_3b2c0588992f5814f49ff099')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1e91179c335a9c0f1447');
            $table->index('account_id', 'ix_a8afa57e5f015a6a3ee6f6f1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_authorizations_authorizations__authorizations');
        Schema::dropIfExists('tl_account_authorizations_authorizations');
    }
};
