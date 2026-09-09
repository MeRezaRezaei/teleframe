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
        Schema::create('tl_secure_value', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5e173f67195d195688cf9be0');
            $table->index('account_id', 'ix_17d8746eb74906709653db3b');
        });
        Schema::create('tl_secure_value_secure_value', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('tl_type');
            $table->index('tl_type', 'ix_98ece3882e1cea730c220185');
            $table->uuid('data')->nullable();
            $table->index('data', 'ix_ded40635fd27884c58eb056a');
            $table->uuid('front_side')->nullable();
            $table->index('front_side', 'ix_27ff4fcf8dacce90b4c3c98b');
            $table->uuid('reverse_side')->nullable();
            $table->index('reverse_side', 'ix_0731e428b3ca0539e1e851f3');
            $table->uuid('selfie')->nullable();
            $table->index('selfie', 'ix_659e861e3a5f80bfb00ddd05');
            $table->uuid('plain_data')->nullable();
            $table->index('plain_data', 'ix_9f0fcd0ab0da4ebea24e58be');
            $table->binary('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f8d81f3250374ef97a07f24f');
        });
        Schema::create('tl_secure_value_secure_value__translation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_secure_value_secure_value')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ddeb26c2c6638797b991');
            $table->index('account_id', 'ix_fac4ebe83c618f3a327c7c4e');
        });
        Schema::create('tl_secure_value_secure_value__files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_secure_value_secure_value')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ea7e2e151c772621cae1');
            $table->index('account_id', 'ix_a23fb86957364cb252000de7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_value_secure_value__files');
        Schema::dropIfExists('tl_secure_value_secure_value__translation');
        Schema::dropIfExists('tl_secure_value_secure_value');
        Schema::dropIfExists('tl_secure_value');
    }
};
