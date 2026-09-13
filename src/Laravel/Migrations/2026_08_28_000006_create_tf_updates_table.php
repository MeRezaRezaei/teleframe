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
        Schema::create('tf_updates', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->primary('id');
            $table->bigInteger('constructor_id');
            $table->bigInteger('account_id');
            $table->bigInteger('peer_id')->nullable();
            $table->integer('message_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->integer('pts')->nullable();
            $table->integer('pts_count')->nullable();
            $table->integer('date')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();
            $table->index('account_id', 'ix_tf_updates_account_id');
            $table->index(['peer_id', 'account_id'], 'ix_tf_updates_peer_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_updates');
    }
};
