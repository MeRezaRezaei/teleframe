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
        Schema::create('tl_help_user_info_user_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('message')->nullable();
            $table->text('author')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_15386c54d5e9344f898cca4c');
            $table->index('account_id', 'ix_27c51566f7d7688e3e826568');
        });
        Schema::create('tl_help_user_info_user_info__entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f3e476584b92314f6d6484d1')->references('id')->on('tl_help_user_info_user_info')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4f0146700c0203e8ebb5');
            $table->index('account_id', 'ix_ec708059d1ffc9c30d8914ab');
        });
        Schema::create('tl_help_user_info_user_info_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_25689132ee57a1eae5ba8820');
            $table->index('account_id', 'ix_d3c5242ce81d11014d085ca9');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_user_info_user_info_empty');
        Schema::dropIfExists('tl_help_user_info_user_info__entities');
        Schema::dropIfExists('tl_help_user_info_user_info');
    }
};
