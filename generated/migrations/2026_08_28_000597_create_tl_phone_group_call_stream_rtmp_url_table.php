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
        Schema::create('tl_phone_group_call_stream_rtmp_url', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_phone_group_call_stream_rtmp_url_group_cal_d42377333fcd', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_phone_group_call_stream_rtmp_url')->cascadeOnDelete();
            $table->text('url');
            $table->text('tl_key');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_group_call_stream_rtmp_url_group_cal_d42377333fcd');
        Schema::dropIfExists('tl_phone_group_call_stream_rtmp_url');
    }
};
