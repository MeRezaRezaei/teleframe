<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Vault schema drift fix: the original create migration for telegram_accounts
 * (2026_09_09_000201) shipped without the `user_id` column even though the
 * TelegramAccount model, the login finalize controller and the vault console
 * commands (vault:add-account / vault:list / vault:use-default) all read and
 * write it. Existing installs — where 2026_09_09_000201 already ran — get the
 * column here; fresh installs already have it from the corrected create
 * migration, so this ALTER must stay idempotent (hasColumn guard).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('telegram_accounts', 'user_id')) {
            return;
        }

        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('telegram_accounts', 'user_id')) {
            return;
        }

        Schema::table('telegram_accounts', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
