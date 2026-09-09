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
        Schema::create('tl_messages_bot_prepared_inline_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_530059c2e524681ae9dffb80');
            $table->index('account_id', 'ix_baa45f4378312a56b8033fc3');
        });
        Schema::create('tl_messages_bot_prepared_inline_message_bot_p_d161024c1fed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_bot_prepared_inline_message')->cascadeOnDelete();
            $table->text('tl_id');
            $table->integer('expire_date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f11914430ddb64345fba01c0');
            $table->unique(['account_id', 'tl_id'], 'ux_55e3c46038fac11868bc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_bot_prepared_inline_message_bot_p_d161024c1fed');
        Schema::dropIfExists('tl_messages_bot_prepared_inline_message');
    }
};
