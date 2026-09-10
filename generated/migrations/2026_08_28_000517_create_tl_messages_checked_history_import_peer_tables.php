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
        Schema::create('tl_messages_checked_history_import_peer_check_abbf04f3a8aa', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('confirm_text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c6ba8245dfbe39d2ba08abfb');
            $table->index('account_id', 'ix_58c167caf6e7e82ccafeec4b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_checked_history_import_peer_check_abbf04f3a8aa');
    }
};
