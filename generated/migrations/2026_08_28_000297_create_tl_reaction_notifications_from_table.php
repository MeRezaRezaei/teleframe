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
        Schema::create('tl_reaction_notifications_from', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_reaction_notifications_from_reaction_notif_70e6503a48b0', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reaction_notifications_from')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_reaction_notifications_from_reaction_notif_d9fdd2611884', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_reaction_notifications_from')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_reaction_notifications_from_reaction_notif_d9fdd2611884');
        Schema::dropIfExists('tl_reaction_notifications_from_reaction_notif_70e6503a48b0');
        Schema::dropIfExists('tl_reaction_notifications_from');
    }
};
