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
        Schema::create('tl_input_quick_reply_shortcut_input_quick_reply_shortcut', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('shortcut')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_621656965effa9ef45dce244');
            $table->index('account_id', 'ix_30f232af3fa2e2ce86736e69');
        });
        Schema::create('tl_input_quick_reply_shortcut_input_quick_rep_7d036b24116c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('shortcut_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3e5d53ca6117bfcbf3d597f6');
            $table->index('account_id', 'ix_f1bda7066e94a3e22f884fc6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_quick_reply_shortcut_input_quick_rep_7d036b24116c');
        Schema::dropIfExists('tl_input_quick_reply_shortcut_input_quick_reply_shortcut');
    }
};
