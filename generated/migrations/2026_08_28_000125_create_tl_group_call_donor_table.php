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
        Schema::create('tl_group_call_donor', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_82040adf224100fe23c7f2d7');
            $table->index('account_id', 'ix_92101685a75a6bea7cbc21f8');
        });
        Schema::create('tl_group_call_donor_group_call_donor', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_group_call_donor')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('top')->default(false);
            $table->boolean('my')->default(false);
            $table->bigInteger('peer_id')->nullable();
            $table->index('peer_id', 'ix_6ab572dc9acdeb982de95629');
            $table->bigInteger('stars');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_946bedb8976d7bf41a6da42f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_group_call_donor_group_call_donor');
        Schema::dropIfExists('tl_group_call_donor');
    }
};
