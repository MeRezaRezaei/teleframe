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
        Schema::create('tl_messages_quick_replies_quick_replies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_54a103bb04a72423253474fa');
            $table->index('account_id', 'ix_15ec32f79d0ccb175df60a85');
        });
        Schema::create('tl_messages_quick_replies_quick_replies__quick_replies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_bac8e7159ccad62b1b89ba9c')->references('id')->on('tl_messages_quick_replies_quick_replies')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_443df8577df6e233ba3d');
            $table->index('account_id', 'ix_2da5f48c239ba574ed44e6de');
        });
        Schema::create('tl_messages_quick_replies_quick_replies__messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_39ff8f145937f66d3ee4a8cf')->references('id')->on('tl_messages_quick_replies_quick_replies')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4aacb9cbba52a31467c8');
            $table->index('account_id', 'ix_357fed745b73acbc92ff3ad8');
        });
        Schema::create('tl_messages_quick_replies_quick_replies__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_bd830d5b3f3696fda3cdecdb')->references('id')->on('tl_messages_quick_replies_quick_replies')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_76a656d69ca21dffbccf');
            $table->index('account_id', 'ix_930ab048b9bf908feddef324');
        });
        Schema::create('tl_messages_quick_replies_quick_replies__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_79eaf01335989ecf4bbee5dd')->references('id')->on('tl_messages_quick_replies_quick_replies')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_820c0f0d047345664def');
            $table->index('account_id', 'ix_c030ee982da0373aca5a23e9');
        });
        Schema::create('tl_messages_quick_replies_quick_replies_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c3188e1765e6f78e5a44d5b7');
            $table->index('account_id', 'ix_3fb7290c460d3888814fc58c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies_not_modified');
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies__users');
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies__chats');
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies__messages');
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies__quick_replies');
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies');
    }
};
