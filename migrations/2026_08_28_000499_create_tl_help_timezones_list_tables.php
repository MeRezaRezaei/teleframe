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
        Schema::create('tl_help_timezones_list_timezones_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d07549c5226c02fdee8d5a29');
            $table->index('account_id', 'ix_e187fb2df1ebd74ec4e76bb3');
        });
        Schema::create('tl_help_timezones_list_timezones_list__timezones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_timezones_list_timezones_list', 'id', 'fk_2d9fd33f3d5c6a7c9d351827')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bc70deb6b2a395ee120c');
            $table->index('account_id', 'ix_5a3afcd06cfb1d3f8880bfb8');
        });
        Schema::create('tl_help_timezones_list_timezones_list_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_44452949029dc2a2bd77ae0f');
            $table->index('account_id', 'ix_050add5386772f2f4b63ee9f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_timezones_list_timezones_list_not_modified');
        Schema::dropIfExists('tl_help_timezones_list_timezones_list__timezones');
        Schema::dropIfExists('tl_help_timezones_list_timezones_list');
    }
};
