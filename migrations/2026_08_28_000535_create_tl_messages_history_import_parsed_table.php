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
        Schema::create('tl_messages_history_import_parsed', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3b20be11d25620d1c2493eb8');
            $table->index('account_id', 'ix_27f8f77c51addbbdc41fa7a5');
        });
        Schema::create('tl_messages_history_import_parsed_history_import_parsed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_history_import_parsed')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pm')->default(false);
            $table->boolean('tl_group')->default(false);
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_494c29768f115a3c03cd5b9e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_history_import_parsed_history_import_parsed');
        Schema::dropIfExists('tl_messages_history_import_parsed');
    }
};
