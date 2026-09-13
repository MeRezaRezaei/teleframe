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
        Schema::create('tf_messages', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->integer('message_id');
            $table->bigInteger('peer_id');
            $table->bigInteger('from_id')->nullable();
            $table->integer('date');
            $table->bigInteger('constructor_id');
            $table->bigInteger('account_id');
            $table->primary('id');
            $table->boolean('is_out')->default(false);
            $table->boolean('is_mentioned')->default(false);
            $table->boolean('is_silent')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->text('message_text')->nullable();
            $table->text('media_type')->nullable();
            $table->integer('reply_to_msg_id')->nullable();
            $table->jsonb('tl_data');
            $table->timestamps();
            $table->unique(['peer_id', 'message_id', 'account_id'], 'ux_tf_messages_scope');
            $table->index(['peer_id', 'date'], 'ix_tf_messages_peer_date');
            $table->index('from_id', 'ix_tf_messages_from_id')->where('from_id');
            $table->index('account_id', 'ix_tf_messages_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_messages');
    }
};
