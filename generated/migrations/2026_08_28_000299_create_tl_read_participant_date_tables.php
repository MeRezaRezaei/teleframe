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
        Schema::create('tl_read_participant_date_read_participant_date', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_b6378e2e0d9ce299a79010d8');
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8bd4300ae366cedd22d278d6');
            $table->index('account_id', 'ix_a22dbdfd58bf0f3c9e978b33');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_read_participant_date_read_participant_date');
    }
};
