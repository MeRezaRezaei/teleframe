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
        Schema::create('tl_input_bot_app', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c5b894fc8842cd53d4e92c72');
            $table->index('account_id', 'ix_d19fe37cc9be8e025818bf58');
        });
        Schema::create('tl_input_bot_app_input_bot_app_i_d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_app')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5a036861ef31d4c57e643a22');
            $table->unique(['account_id', 'tl_id'], 'ux_7b066168d37774544830');
        });
        Schema::create('tl_input_bot_app_input_bot_app_short_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_app')->cascadeOnDelete();
            $table->uuid('bot_id');
            $table->index('bot_id', 'ix_52ad598ee67ec36a514ec5ae');
            $table->text('short_name');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0bb6147d502b7fd71a47bdbc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_bot_app_input_bot_app_short_name');
        Schema::dropIfExists('tl_input_bot_app_input_bot_app_i_d');
        Schema::dropIfExists('tl_input_bot_app');
    }
};
