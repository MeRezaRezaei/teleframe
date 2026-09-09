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
        Schema::create('tl_business_away_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c863616068befc7268a6e7e1');
            $table->index('account_id', 'ix_7835112f426a3d52c4aeff03');
        });
        Schema::create('tl_business_away_message_business_away_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_away_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('offline_only')->default(false);
            $table->integer('shortcut_id');
            $table->uuid('schedule');
            $table->index('schedule', 'ix_0f22de4f709eaa35292bbf10');
            $table->uuid('recipients');
            $table->index('recipients', 'ix_b75032b91bbea5cf0c3baa3c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_91fca124ad63cd2dd149212e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_away_message_business_away_message');
        Schema::dropIfExists('tl_business_away_message');
    }
};
