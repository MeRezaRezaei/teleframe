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
        Schema::create('tl_send_as_peer_send_as_peer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('premium_required')->default(false);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_4e7258369786775a555534a6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f04f3cc2756bc885ee706456');
            $table->index('account_id', 'ix_1defb0d5be8b25a9262362b2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_send_as_peer_send_as_peer');
    }
};
