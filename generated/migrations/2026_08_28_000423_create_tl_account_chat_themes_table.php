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
        Schema::create('tl_account_chat_themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_f813669979d919c57ddfef43');
            $table->index('account_id', 'ix_5a5e6361de6ae536d2c0ab2d');
        });
        Schema::create('tl_account_chat_themes_chat_themes', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_chat_themes')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('hash');
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c7d6717409039f4cd504e9fc');
        });
        Schema::create('tl_account_chat_themes_chat_themes__themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_chat_themes_chat_themes')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_db95a5e52d2f4cfa98d1');
            $table->index('account_id', 'ix_9f76462b71af5d6fb76f8cb4');
        });
        Schema::create('tl_account_chat_themes_chat_themes__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_chat_themes_chat_themes')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6364c9624de1ee497135');
            $table->index('account_id', 'ix_b6cddc3b87c05d1526a5d761');
        });
        Schema::create('tl_account_chat_themes_chat_themes__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_chat_themes_chat_themes')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_600632584b25becef455');
            $table->index('account_id', 'ix_2ab2f9f62c21cf000c12e3c7');
        });
        Schema::create('tl_account_chat_themes_chat_themes_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_chat_themes')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_865aec022418747abfa84c53');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_chat_themes_chat_themes_not_modified');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes__users');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes__chats');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes__themes');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes');
        Schema::dropIfExists('tl_account_chat_themes');
    }
};
