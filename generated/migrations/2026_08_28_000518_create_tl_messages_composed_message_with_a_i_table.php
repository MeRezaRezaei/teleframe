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
        Schema::create('tl_messages_composed_message_with_a_i', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e07f6d84ad5ffb9662ab7633');
            $table->index('account_id', 'ix_7ecfed6ab549af549587bfa2');
        });
        Schema::create('tl_messages_composed_message_with_a_i_compose_55280cfdf5bd', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_composed_message_with_a_i')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('result_text');
            $table->index('result_text', 'ix_112868f3990a95285076d035');
            $table->uuid('diff_text')->nullable();
            $table->index('diff_text', 'ix_f9ba296d101e063e11613535');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_79ec9f17220eb0d1b50c894e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_composed_message_with_a_i_compose_55280cfdf5bd');
        Schema::dropIfExists('tl_messages_composed_message_with_a_i');
    }
};
