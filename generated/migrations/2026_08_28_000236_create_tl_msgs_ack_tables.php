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
        Schema::create('tl_msgs_ack_msgs_ack', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2262601b910fa15a9e48e00f');
            $table->index('account_id', 'ix_15cc8e5e8250c01862f2742a');
        });
        Schema::create('tl_msgs_ack_msgs_ack__msg_ids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_msgs_ack_msgs_ack', 'id', 'fk_f68e8a7868dec7562bf81a22')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8b61e8e984d394cb3012');
            $table->index('account_id', 'ix_3788ed71422637fed02c488f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_msgs_ack_msgs_ack__msg_ids');
        Schema::dropIfExists('tl_msgs_ack_msgs_ack');
    }
};
