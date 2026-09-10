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
        Schema::create('tl_messages_affected_history_affected_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('pts')->nullable();
            $table->integer('pts_count')->nullable();
            $table->integer('tl_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f268a6133e105be90ea8df92');
            $table->index('account_id', 'ix_70894d1e862fb32af32eae51');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_affected_history_affected_history');
    }
};
