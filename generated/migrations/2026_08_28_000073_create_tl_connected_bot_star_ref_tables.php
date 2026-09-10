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
        Schema::create('tl_connected_bot_star_ref_connected_bot_star_ref', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('revoked')->default(false);
            $table->text('url')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_d0f560cbf470a3cc0aa9e48c');
            $table->integer('commission_permille')->nullable();
            $table->integer('duration_months')->nullable();
            $table->bigInteger('participants')->nullable();
            $table->bigInteger('revenue')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7a37ab0feb223a216a6922d8');
            $table->index('account_id', 'ix_35d39087f01901288121161c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_connected_bot_star_ref_connected_bot_star_ref');
    }
};
