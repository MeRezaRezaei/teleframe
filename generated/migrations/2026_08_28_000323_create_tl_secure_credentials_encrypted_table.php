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
        Schema::create('tl_secure_credentials_encrypted', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_4c019bb0d3d653d58969a3ca');
            $table->index('account_id', 'ix_97ad3e7ad0cc16584d2194b1');
        });
        Schema::create('tl_secure_credentials_encrypted_secure_creden_5d7271a97981', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_credentials_encrypted')->cascadeOnDelete();
            $table->binary('data');
            $table->binary('hash');
            $table->binary('secret');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ced44552b1610704cd599319');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_credentials_encrypted_secure_creden_5d7271a97981');
        Schema::dropIfExists('tl_secure_credentials_encrypted');
    }
};
