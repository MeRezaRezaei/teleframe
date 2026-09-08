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
        Schema::create('tl_missing_invitee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_missing_invitee_missing_invitee', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_missing_invitee')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('premium_would_allow_invite')->default(false);
            $table->boolean('premium_required_for_pm')->default(false);
            $table->bigInteger('user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_missing_invitee_missing_invitee');
        Schema::dropIfExists('tl_missing_invitee');
    }
};
