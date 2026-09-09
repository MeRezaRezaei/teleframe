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
        Schema::create('tl_help_passport_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e97cecbcbd423808e537ffc8');
            $table->index('account_id', 'ix_f213d707fad116a3c593863d');
        });
        Schema::create('tl_help_passport_config_passport_config', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_passport_config')->cascadeOnDelete();
            $table->integer('hash');
            $table->uuid('countries_langs');
            $table->index('countries_langs', 'ix_69f6cf9c7500b033975b5924');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1ddc6614b9152727bdde2a84');
        });
        Schema::create('tl_help_passport_config_passport_config_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_passport_config')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e7ba98f8b99e32d90ec8db1f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_passport_config_passport_config_not_modified');
        Schema::dropIfExists('tl_help_passport_config_passport_config');
        Schema::dropIfExists('tl_help_passport_config');
    }
};
