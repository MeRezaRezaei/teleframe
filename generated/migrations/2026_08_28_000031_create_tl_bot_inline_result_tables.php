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
        Schema::create('tl_bot_inline_result_bot_inline_media_result', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id')->nullable();
            $table->text('tl_type')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_861a1d620583adf89607f81e');
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_fc37377a45150f12741a8704');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('send_message')->nullable();
            $table->index('send_message', 'ix_25cb1efda190a60d2543a297');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_eda7fd6c3654a577fda934a1');
            $table->index('account_id', 'ix_061a5fb1d3a7a503bc813f0a');
        });
        Schema::create('tl_bot_inline_result_bot_inline_result', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id')->nullable();
            $table->text('tl_type')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('thumb')->nullable();
            $table->index('thumb', 'ix_bd419356eee87814b9a42ab0');
            $table->bigInteger('content')->nullable();
            $table->index('content', 'ix_988f3ae357589468a97a3055');
            $table->bigInteger('send_message')->nullable();
            $table->index('send_message', 'ix_0a36f9d2cbcba9dc6658dc22');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_34d2ed28c5f63a3f1c658ed5');
            $table->index('account_id', 'ix_f793d2013c96578363927c76');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_inline_result_bot_inline_result');
        Schema::dropIfExists('tl_bot_inline_result_bot_inline_media_result');
    }
};
