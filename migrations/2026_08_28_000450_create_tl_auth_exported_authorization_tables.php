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
        Schema::create('tl_auth_exported_authorization_exported_authorization', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id');
            $table->binary('bytes');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_410a0a82d51ac527ffa72388');
            $table->index('account_id', 'ix_fab8e542d1f6040d73431f9c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_exported_authorization_exported_authorization');
    }
};
