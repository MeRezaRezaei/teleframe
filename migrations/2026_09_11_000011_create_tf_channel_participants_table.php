<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_channel_participants', function (Blueprint $table) {
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->bigInteger('account_id');
            $table->integer('date')->nullable();
            $table->text('rank')->nullable();
            $table->bigInteger('inviter_id')->nullable();
            $table->bigInteger('promoted_by')->nullable();
            $table->bigInteger('kicked_by')->nullable();
            $table->boolean('is_via_request')->default(false);
            $table->jsonb('tl_data');
            $table->timestamps();

            $table->primary(['channel_id', 'user_id', 'account_id']);
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_channel_participants');
    }
};
