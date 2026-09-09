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
        Schema::create('tl_input_quick_reply_shortcut', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5cafcca5487fd7988fb985a9');
            $table->index('account_id', 'ix_94faec012ac2ef5c0c21f2ac');
        });
        Schema::create('tl_input_quick_reply_shortcut_input_quick_reply_shortcut', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_quick_reply_shortcut')->cascadeOnDelete();
            $table->text('shortcut');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_30f232af3fa2e2ce86736e69');
        });
        Schema::create('tl_input_quick_reply_shortcut_input_quick_rep_7d036b24116c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_quick_reply_shortcut')->cascadeOnDelete();
            $table->integer('shortcut_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f1bda7066e94a3e22f884fc6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_quick_reply_shortcut_input_quick_rep_7d036b24116c');
        Schema::dropIfExists('tl_input_quick_reply_shortcut_input_quick_reply_shortcut');
        Schema::dropIfExists('tl_input_quick_reply_shortcut');
    }
};
