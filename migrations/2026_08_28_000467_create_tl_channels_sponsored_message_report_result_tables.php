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
        Schema::create('tl_channels_sponsored_message_report_result_s_a8bb878b93c6', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a41e782596411bb231c8fe54');
            $table->index('account_id', 'ix_13250a9b9cf4c90400bbc062');
        });
        Schema::create('tl_channels_sponsored_message_report_result_s_90d28813b853', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_132f7c777731c21c1597583d');
            $table->index('account_id', 'ix_b89df695678723f8f0652b84');
        });
        Schema::create('tl_channels_sponsored_message_report_result_s_4f877f3b1319', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_eba12914aba18dd6ca1272cb')->references('id')->on('tl_channels_sponsored_message_report_result_s_90d28813b853')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4e2a8693c9a7059963bd');
            $table->index('account_id', 'ix_2ed5eda5a2a122c000d992bc');
        });
        Schema::create('tl_channels_sponsored_message_report_result_s_0c801a5ac42c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5b3be61443d7beac57df88cd');
            $table->index('account_id', 'ix_566d24e21e5d680fa7184f0b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channels_sponsored_message_report_result_s_0c801a5ac42c');
        Schema::dropIfExists('tl_channels_sponsored_message_report_result_s_4f877f3b1319');
        Schema::dropIfExists('tl_channels_sponsored_message_report_result_s_90d28813b853');
        Schema::dropIfExists('tl_channels_sponsored_message_report_result_s_a8bb878b93c6');
    }
};
