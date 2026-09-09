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
        Schema::create('tl_help_app_config', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_38792ce2a6a2c5817f88c706');
            $table->index('account_id', 'ix_662a55f5a2a212fcb01d60da');
        });
        Schema::create('tl_help_app_config_app_config', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_app_config')->cascadeOnDelete();
            $table->integer('hash');
            $table->uuid('config');
            $table->index('config', 'ix_468b2196623818496026fff7');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a41a344bf0a886a70f20ba40');
        });
        Schema::create('tl_help_app_config_app_config_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_help_app_config')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_22904b166f87b85a133e7e71');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_app_config_app_config_not_modified');
        Schema::dropIfExists('tl_help_app_config_app_config');
        Schema::dropIfExists('tl_help_app_config');
    }
};
