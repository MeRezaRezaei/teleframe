<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Credential vault (apps): one row per my.telegram.org application.
 * Owner morph nullable (same pattern as tl_user_bindings — works with NO
 * Laravel User, plain-PHP apps). api_hash stored with the `encrypted`
 * Eloquent cast on the model (ciphertext at rest, APP_KEY via Laravel
 * encrypter). Label unique: the named handle `TF::vault()->app($label)`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_apps', function (Blueprint $table) {
            $table->id();
            $table->string('label', 255)->unique();
            $table->string('owner_type', 255)->nullable();
            $table->bigInteger('owner_id')->nullable();
            $table->bigInteger('api_id');
            $table->text('api_hash');
            $table->timestamps();
        });
        Schema::table('telegram_apps', function (Blueprint $table) {
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_apps');
    }
};
