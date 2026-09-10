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
        Schema::create('tl_account_saved_ringtones_saved_ringtones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_03b9231d0803668360161909');
            $table->index('account_id', 'ix_96829db78a2227eb4b5ec401');
        });
        Schema::create('tl_account_saved_ringtones_saved_ringtones__ringtones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_cf0945d27d6a259bd4ef7104')->references('id')->on('tl_account_saved_ringtones_saved_ringtones')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_21c25cf82217557cac4d');
            $table->index('account_id', 'ix_d29ee28102f80cfe6a69f936');
        });
        Schema::create('tl_account_saved_ringtones_saved_ringtones_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_64340ac1b742b55fa0fdf61a');
            $table->index('account_id', 'ix_c1194b6d26f8458daf3c3128');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_saved_ringtones_saved_ringtones_not_modified');
        Schema::dropIfExists('tl_account_saved_ringtones_saved_ringtones__ringtones');
        Schema::dropIfExists('tl_account_saved_ringtones_saved_ringtones');
    }
};
