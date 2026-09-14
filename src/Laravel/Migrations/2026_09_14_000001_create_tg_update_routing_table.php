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
            $table->unsignedBigInteger('peer_id');
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
