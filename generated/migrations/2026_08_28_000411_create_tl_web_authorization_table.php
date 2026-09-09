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
        Schema::create('tl_web_authorization', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_49e96ae6fd5ef68e50a87a7b');
            $table->index('account_id', 'ix_fa092f1f5aef1562f47ed812');
        });
        Schema::create('tl_web_authorization_web_authorization', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_authorization')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_11377894211190674bc05091');
            $table->text('domain');
            $table->text('browser');
            $table->text('platform');
            $table->integer('date_created');
            $table->integer('date_active');
            $table->text('ip');
            $table->text('region');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6ed3aeba74fabbec7800f38a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_authorization_web_authorization');
        Schema::dropIfExists('tl_web_authorization');
    }
};
