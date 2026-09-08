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
        Schema::create('tl_saved_contact', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_saved_contact_saved_phone_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_saved_contact')->cascadeOnDelete();
            $table->text('phone');
            $table->text('first_name');
            $table->text('last_name');
            $table->integer('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_saved_contact_saved_phone_contact');
        Schema::dropIfExists('tl_saved_contact');
    }
};
