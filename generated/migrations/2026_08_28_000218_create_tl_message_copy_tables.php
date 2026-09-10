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
        Schema::create('tl_message_copy_msg_copy', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('orig_message')->nullable();
            $table->index('orig_message', 'ix_6b6b11cbd4309c9bd6e7a3d0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_41827ed79cf186908f5ac6f3');
            $table->index('account_id', 'ix_b496d12431ead957df74c70b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_copy_msg_copy');
    }
};
