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
        Schema::create('tl_account_saved_music_ids_saved_music_ids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d7322d9742bff78f15dec87b');
            $table->index('account_id', 'ix_0c3c20a5e8a6c94ec21439d8');
        });
        Schema::create('tl_account_saved_music_ids_saved_music_ids__ids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_saved_music_ids_saved_music_ids', 'id', 'fk_4bddaf18c50f301ba47f83e0')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_12e3d4688db4c14714a8');
            $table->index('account_id', 'ix_bb89ff64b40ef3cde1bcd951');
        });
        Schema::create('tl_account_saved_music_ids_saved_music_ids_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3f295c215ce79696b29f7c93');
            $table->index('account_id', 'ix_ad85a20ca26e1c49dd06576e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_saved_music_ids_saved_music_ids_not_modified');
        Schema::dropIfExists('tl_account_saved_music_ids_saved_music_ids__ids');
        Schema::dropIfExists('tl_account_saved_music_ids_saved_music_ids');
    }
};
