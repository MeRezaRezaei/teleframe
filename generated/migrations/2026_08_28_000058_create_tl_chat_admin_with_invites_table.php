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
        Schema::create('tl_chat_admin_with_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_aab7b257821dd4b0ffe5195a');
            $table->index('account_id', 'ix_5a9d1051d50c719c05769d24');
        });
        Schema::create('tl_chat_admin_with_invites_chat_admin_with_invites', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_admin_with_invites')->cascadeOnDelete();
            $table->bigInteger('admin_id');
            $table->index('admin_id', 'ix_85e5775535e175cc98a3b7b5');
            $table->integer('invites_count');
            $table->integer('revoked_invites_count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_76b17b7046391f4186cb2fa9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_admin_with_invites_chat_admin_with_invites');
        Schema::dropIfExists('tl_chat_admin_with_invites');
    }
};
