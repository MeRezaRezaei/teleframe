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
        Schema::create('tl_smsjobs_eligibility_to_join_eligible_to_join', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('terms_url')->nullable();
            $table->integer('monthly_sent_sms')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cbaafdcda56c2fbeee4462fb');
            $table->index('account_id', 'ix_2b2a550c0b79cac1ecd562c9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_smsjobs_eligibility_to_join_eligible_to_join');
    }
};
