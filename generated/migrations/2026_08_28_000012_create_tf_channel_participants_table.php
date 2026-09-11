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
        Schema::create('tf_channel_participants', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->primary('id');
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->bigInteger('constructor_id');
            $table->bigInteger('account_id');
            $table->integer('date')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();
            $table->unique(['channel_id', 'user_id', 'account_id'], 'ux_tf_ch_participants_scope');
            $table->index('account_id', 'ix_tf_ch_participants_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_channel_participants');
    }
};
