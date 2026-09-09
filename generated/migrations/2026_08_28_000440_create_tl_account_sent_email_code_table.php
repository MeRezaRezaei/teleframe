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
        Schema::create('tl_account_sent_email_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_7371ac5eac5a67084e970950');
            $table->index('account_id', 'ix_1b6bcdec039a792c02cd0b71');
        });
        Schema::create('tl_account_sent_email_code_sent_email_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_sent_email_code')->cascadeOnDelete();
            $table->text('email_pattern');
            $table->integer('length');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9dd95184b1aac8789ff921d3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_sent_email_code_sent_email_code');
        Schema::dropIfExists('tl_account_sent_email_code');
    }
};
