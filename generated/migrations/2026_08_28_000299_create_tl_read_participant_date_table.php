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
        Schema::create('tl_read_participant_date', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5360ecadb9eb2acb2c2b166e');
            $table->index('account_id', 'ix_370b59f8bfd40c47592e51f1');
        });
        Schema::create('tl_read_participant_date_read_participant_date', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_read_participant_date')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_b6378e2e0d9ce299a79010d8');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a22dbdfd58bf0f3c9e978b33');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_read_participant_date_read_participant_date');
        Schema::dropIfExists('tl_read_participant_date');
    }
};
