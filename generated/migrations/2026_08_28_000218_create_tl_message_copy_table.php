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
        Schema::create('tl_message_copy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_17e843819076c1b42fccd782');
            $table->index('account_id', 'ix_f107f8a120836014dfc101d6');
        });
        Schema::create('tl_message_copy_msg_copy', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_copy')->cascadeOnDelete();
            $table->uuid('orig_message');
            $table->index('orig_message', 'ix_6b6b11cbd4309c9bd6e7a3d0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b496d12431ead957df74c70b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_copy_msg_copy');
        Schema::dropIfExists('tl_message_copy');
    }
};
