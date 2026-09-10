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
        Schema::create('tl_phone_group_call_stream_channels_group_cal_7df01b0705a4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_65f253ae6127f16484d93a05');
            $table->index('account_id', 'ix_45033376c53b30ccbc9ac958');
        });
        Schema::create('tl_phone_group_call_stream_channels_group_cal_34013b05c8fd', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_b4bd054b62474ca871bc4d26')->references('id')->on('tl_phone_group_call_stream_channels_group_cal_7df01b0705a4')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a3ecad8aa461d366b3ba');
            $table->index('account_id', 'ix_b0a4e01679e96d2265bf00b5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_phone_group_call_stream_channels_group_cal_34013b05c8fd');
        Schema::dropIfExists('tl_phone_group_call_stream_channels_group_cal_7df01b0705a4');
    }
};
