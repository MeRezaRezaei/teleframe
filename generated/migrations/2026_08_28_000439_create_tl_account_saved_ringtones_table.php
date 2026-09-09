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
        Schema::create('tl_account_saved_ringtones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_27653ba17a7a02a6d0f78831');
            $table->index('account_id', 'ix_2fc27d396ffb57636443e9fa');
        });
        Schema::create('tl_account_saved_ringtones_saved_ringtones', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_saved_ringtones')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_96829db78a2227eb4b5ec401');
        });
        Schema::create('tl_account_saved_ringtones_saved_ringtones__ringtones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_saved_ringtones_saved_ringtones')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_21c25cf82217557cac4d');
            $table->index('account_id', 'ix_d29ee28102f80cfe6a69f936');
        });
        Schema::create('tl_account_saved_ringtones_saved_ringtones_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_saved_ringtones')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c1194b6d26f8458daf3c3128');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_saved_ringtones_saved_ringtones_not_modified');
        Schema::dropIfExists('tl_account_saved_ringtones_saved_ringtones__ringtones');
        Schema::dropIfExists('tl_account_saved_ringtones_saved_ringtones');
        Schema::dropIfExists('tl_account_saved_ringtones');
    }
};
