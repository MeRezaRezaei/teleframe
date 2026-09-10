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
        Schema::create('tl_poll_answer_voters_poll_answer_voters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('chosen')->default(false);
            $table->boolean('correct')->default(false);
            $table->binary('option')->nullable();
            $table->integer('voters')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f00f612d4868096fa6a5a5ea');
            $table->index('account_id', 'ix_8ed039f662d9e1cdbf4b4dc3');
        });
        Schema::create('tl_poll_answer_voters_poll_answer_voters__recent_voters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_poll_answer_voters_poll_answer_voters', 'id', 'fk_d4bebaaf2cea71fb49d5697d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_525be0a4c7d7a93f54e8');
            $table->index('account_id', 'ix_8767f8ae4c8f10de1f8dcef3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_poll_answer_voters_poll_answer_voters__recent_voters');
        Schema::dropIfExists('tl_poll_answer_voters_poll_answer_voters');
    }
};
