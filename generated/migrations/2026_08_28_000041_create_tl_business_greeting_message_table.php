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
        Schema::create('tl_business_greeting_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_18c3d064be75f7b18ed1fffc');
            $table->index('account_id', 'ix_e50ebaab26e442e97f929eb6');
        });
        Schema::create('tl_business_greeting_message_business_greeting_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_greeting_message')->cascadeOnDelete();
            $table->integer('shortcut_id');
            $table->uuid('recipients');
            $table->index('recipients', 'ix_1ade913ea8b89a133edf0015');
            $table->integer('no_activity_days');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_afb5aa13f02968b8359c9cc0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_greeting_message_business_greeting_message');
        Schema::dropIfExists('tl_business_greeting_message');
    }
};
