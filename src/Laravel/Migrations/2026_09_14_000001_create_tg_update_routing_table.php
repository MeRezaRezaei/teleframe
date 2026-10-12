<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tg_update_routing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedTinyInteger('peer_type');
            // SIGNED on purpose, and it must match the mirror. Telegram chat
            // and channel ids are negative (-1001234567890 for a supergroup).
            // Unsigned rejects them outright on MySQL, while Postgres and
            // SQLite silently drop the modifier — so a route for any group or
            // channel worked everywhere except MySQL, and the 2026_09_14_200010
            // mirror header already documents this exact class of bug as "the
            // old generated mirror stored them UNSIGNED (data corruption for <
            // 0 ids)". It was fixed in the mirror and left unfixed here.
            $table->bigInteger('peer_id');
            $table->string('mode', 32)->default('act_on');
            $table->unsignedTinyInteger('priority')->default(0);
            $table->timestamps();

            $table->unique(['account_id', 'peer_type', 'peer_id']);
            $table->index(['account_id', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tg_update_routing');
    }
};
