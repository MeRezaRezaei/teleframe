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
        Schema::create('tl_auth_password_recovery', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e19cbf2b81e1a5e7a1603642');
            $table->index('account_id', 'ix_61a3dd1f14b215b4697ac11d');
        });
        Schema::create('tl_auth_password_recovery_password_recovery', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_password_recovery')->cascadeOnDelete();
            $table->text('email_pattern');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_94d5dcaf9b00b3607fc0d444');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_password_recovery_password_recovery');
        Schema::dropIfExists('tl_auth_password_recovery');
    }
};
