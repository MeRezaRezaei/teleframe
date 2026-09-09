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
        Schema::create('tl_smsjobs_eligibility_to_join', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_d8fe38f9f9662e00ffaebfc1');
            $table->index('account_id', 'ix_c905ad1833448a34f2fc342f');
        });
        Schema::create('tl_smsjobs_eligibility_to_join_eligible_to_join', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_smsjobs_eligibility_to_join')->cascadeOnDelete();
            $table->text('terms_url');
            $table->integer('monthly_sent_sms');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2b2a550c0b79cac1ecd562c9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_smsjobs_eligibility_to_join_eligible_to_join');
        Schema::dropIfExists('tl_smsjobs_eligibility_to_join');
    }
};
