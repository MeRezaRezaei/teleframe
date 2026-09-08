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
        Schema::create('tl_chat_invite_importer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_chat_invite_importer_chat_invite_importer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_invite_importer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('requested')->default(false);
            $table->boolean('via_chatlist')->default(false);
            $table->bigInteger('user_id');
            $table->integer('date');
            $table->text('about')->nullable();
            $table->bigInteger('approved_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_invite_importer_chat_invite_importer');
        Schema::dropIfExists('tl_chat_invite_importer');
    }
};
