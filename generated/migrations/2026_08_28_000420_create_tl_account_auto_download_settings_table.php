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
        Schema::create('tl_account_auto_download_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8111a36b46beae3aaf891aef');
            $table->index('account_id', 'ix_3f59acecdff6fd1a2ce7c643');
        });
        Schema::create('tl_account_auto_download_settings_auto_download_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_auto_download_settings')->cascadeOnDelete();
            $table->uuid('low');
            $table->index('low', 'ix_429ed29e0be1441fd0a54154');
            $table->uuid('medium');
            $table->index('medium', 'ix_b662f76db0bd56eeb04fb9b7');
            $table->uuid('high');
            $table->index('high', 'ix_cabfca3664c9560bd0b3fa27');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f8abca64ed9ebdf6dc5bfb9b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_auto_download_settings_auto_download_settings');
        Schema::dropIfExists('tl_account_auto_download_settings');
    }
};
