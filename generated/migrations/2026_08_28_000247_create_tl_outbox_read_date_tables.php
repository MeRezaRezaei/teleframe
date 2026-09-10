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
        Schema::create('tl_outbox_read_date_outbox_read_date', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e1912e4151a05069b3b59800');
            $table->index('account_id', 'ix_9c426bc16f596b0ec91f1a4b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_outbox_read_date_outbox_read_date');
    }
};
