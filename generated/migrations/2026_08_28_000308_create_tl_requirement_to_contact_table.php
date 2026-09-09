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
            $table->index('constructor_id', 'ix_323a2d39132bd801de6698e9');
            $table->index('account_id', 'ix_7626616954aa170a11ec80cd');
        });
        Schema::create('tl_requirement_to_contact_requirement_to_contact_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requirement_to_contact')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_26e611bf0f603e1d4f26d9b0');
        });
        Schema::create('tl_requirement_to_contact_requirement_to_cont_be6f2f636604', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requirement_to_contact')->cascadeOnDelete();
            $table->bigInteger('stars_amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_edcb1321cfad3892de0f3d9d');
        });
        Schema::create('tl_requirement_to_contact_requirement_to_contact_premium', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requirement_to_contact')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_93bbc2989192b9fcd7baae83');
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
