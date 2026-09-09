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
        Schema::create('tl_secure_value_hash', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b2a613201e47c466b265e741');
            $table->index('account_id', 'ix_b10492c295b1b7dbde0e4883');
        });
        Schema::create('tl_secure_value_hash_secure_value_hash', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_hash')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->index('tl_type', 'ix_f930ae7cf9b8eaf7884e2a32');
            $table->binary('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a471b734916510d27c7e26ba');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_value_hash_secure_value_hash');
        Schema::dropIfExists('tl_secure_value_hash');
    }
};
