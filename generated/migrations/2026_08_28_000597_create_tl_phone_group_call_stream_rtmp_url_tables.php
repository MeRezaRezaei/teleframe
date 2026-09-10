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
        Schema::create('tl_phone_group_call_stream_rtmp_url_group_cal_d42377333fcd', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->text('tl_key')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_839c70d28a5b470ddb2984e1');
            $table->index('account_id', 'ix_e665700d8bd495a114a91455');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_group_call_stream_rtmp_url_group_cal_d42377333fcd');
    }
};
