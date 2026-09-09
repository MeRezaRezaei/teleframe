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
        Schema::create('tl_input_contact', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_d3ec87006787540eeb52b84f');
            $table->index('account_id', 'ix_6bb4fc72188aae488cb6b061');
        });
        Schema::create('tl_input_contact_input_phone_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_contact')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('client_id');
            $table->index('client_id', 'ix_da35da78a7977dea4def049c');
            $table->text('phone');
            $table->text('first_name');
            $table->text('last_name');
            $table->uuid('note')->nullable();
            $table->index('note', 'ix_8ba7039588624a831864316c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9c6e7707b0b6b29fdc18992b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_contact_input_phone_contact');
        Schema::dropIfExists('tl_input_contact');
    }
};
