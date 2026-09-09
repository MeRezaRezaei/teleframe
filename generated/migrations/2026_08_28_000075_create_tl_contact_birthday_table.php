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
        Schema::create('tl_contact_birthday', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_7a04d56632565088f46f4ace');
            $table->index('account_id', 'ix_4d6f277c269a023f60efd655');
        });
        Schema::create('tl_contact_birthday_contact_birthday', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contact_birthday')->cascadeOnDelete();
            $table->bigInteger('contact_id');
            $table->index('contact_id', 'ix_209bc14dcb3530c851e9c3c5');
            $table->uuid('birthday');
            $table->index('birthday', 'ix_523b36a8d1baa6f74b5b1d9b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e9915faa415f4fc743e42718');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contact_birthday_contact_birthday');
        Schema::dropIfExists('tl_contact_birthday');
    }
};
