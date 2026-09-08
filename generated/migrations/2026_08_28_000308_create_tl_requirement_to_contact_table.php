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
        Schema::create('tl_requirement_to_contact', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_requirement_to_contact_requirement_to_contact_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requirement_to_contact')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_requirement_to_contact_requirement_to_cont_be6f2f636604', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requirement_to_contact')->cascadeOnDelete();
            $table->bigInteger('stars_amount');
            $table->timestamps();
        });
        Schema::create('tl_requirement_to_contact_requirement_to_contact_premium', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requirement_to_contact')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_requirement_to_contact_requirement_to_contact_premium');
        Schema::dropIfExists('tl_requirement_to_contact_requirement_to_cont_be6f2f636604');
        Schema::dropIfExists('tl_requirement_to_contact_requirement_to_contact_empty');
        Schema::dropIfExists('tl_requirement_to_contact');
    }
};
