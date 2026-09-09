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
        Schema::create('tl_contact_status', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_078b54e086733d2e3245a481');
            $table->index('account_id', 'ix_905bdbaa3627d3dba8828561');
        });
        Schema::create('tl_contact_status_contact_status', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contact_status')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_43b66d72ca1a08960641dad3');
            $table->uuid('status');
            $table->index('status', 'ix_9805e238c3ec66430dca1a18');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f28ab6f83e41f38da3f8e4bd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contact_status_contact_status');
        Schema::dropIfExists('tl_contact_status');
    }
};
