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
        Schema::create('tl_msgs_all_info_msgs_all_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('info')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2f25f9efea6464078f29909b');
            $table->index('account_id', 'ix_c9f353db4f2d41318da68eea');
        });
        Schema::create('tl_msgs_all_info_msgs_all_info__msg_ids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_msgs_all_info_msgs_all_info', 'id', 'fk_2f1f76bce5bbb06185fd58df')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b3048327ad87a1c5f779');
            $table->index('account_id', 'ix_9a448ab01d8a2ce66ce3e3ed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_msgs_all_info_msgs_all_info__msg_ids');
        Schema::dropIfExists('tl_msgs_all_info_msgs_all_info');
    }
};
