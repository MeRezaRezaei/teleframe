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
        Schema::create('tl_peer_blocked_peer_blocked', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer_id')->nullable();
            $table->index('peer_id', 'ix_9404f8012d8b4cd36cf5f4f5');
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c372ef64c1226cc29ca543d4');
            $table->index('account_id', 'ix_3b404765df54788da9986adf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_blocked_peer_blocked');
    }
};
