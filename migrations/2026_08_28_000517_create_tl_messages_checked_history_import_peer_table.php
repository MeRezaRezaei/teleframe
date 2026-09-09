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
        Schema::create('tl_messages_checked_history_import_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_30e548d377e982913b7fd6b5');
            $table->index('account_id', 'ix_833a0e2a71c5c1ec24ac2282');
        });
        Schema::create('tl_messages_checked_history_import_peer_check_abbf04f3a8aa', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_checked_history_import_peer')->cascadeOnDelete();
            $table->text('confirm_text');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_58c167caf6e7e82ccafeec4b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_checked_history_import_peer_check_abbf04f3a8aa');
        Schema::dropIfExists('tl_messages_checked_history_import_peer');
    }
};
