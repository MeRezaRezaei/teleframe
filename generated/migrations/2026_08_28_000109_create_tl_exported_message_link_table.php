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
        Schema::create('tl_exported_message_link', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b268cd2570bcf7753d269bef');
            $table->index('account_id', 'ix_8110d612810b17985917a678');
        });
        Schema::create('tl_exported_message_link_exported_message_link', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_exported_message_link')->cascadeOnDelete();
            $table->text('link');
            $table->text('html');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bd0403b8eb0cae124d46d6ea');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_exported_message_link_exported_message_link');
        Schema::dropIfExists('tl_exported_message_link');
    }
};
