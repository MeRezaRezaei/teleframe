<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Phase 5b (Q8 ruling): package-owned binding store between a Laravel User
 * (nullable morph — works with NO Laravel User, plain-PHP apps) and the
 * stable telegram bigint identity (tl_user_id), tenant-scoped by account_id.
 *
 * - Keyed on the STABLE telegram id (tl_user_id), never the anchor UUID.
 * - Binding must OUTLIVE instance deletion: `contact_lost` flag instead of
 *   cascade delete (the morph is un-constrained on purpose — deleting a
 *   Laravel User never cascades into bindings, and vice versa).
 * - unique (tl_user_id, account_id): max one binding per telegram user per
 *   tenant account. account_id is nullable so plain-PHP/no-tenant rows exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tl_user_bindings', function (Blueprint $table) {
            $table->id();
            $table->string('user_type', 255)->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('tl_user_id');
            $table->bigInteger('account_id')->nullable();
            $table->boolean('contact_lost')->default(false);
            $table->timestamps();
            $table->unique(['tl_user_id', 'account_id']);
        });
        Schema::table('tl_user_bindings', function (Blueprint $table) {
            $table->index(['user_type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_user_bindings');
    }
};