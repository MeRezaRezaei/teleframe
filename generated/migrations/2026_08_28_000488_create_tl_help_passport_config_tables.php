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
        Schema::create('tl_help_passport_config_passport_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('hash')->nullable();
            $table->bigInteger('countries_langs')->nullable();
            $table->index('countries_langs', 'ix_69f6cf9c7500b033975b5924');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e56a1ded4b240696d27c64cd');
            $table->index('account_id', 'ix_1ddc6614b9152727bdde2a84');
        });
        Schema::create('tl_help_passport_config_passport_config_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7d30efc6ec5c390a27892381');
            $table->index('account_id', 'ix_e7ba98f8b99e32d90ec8db1f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_passport_config_passport_config_not_modified');
        Schema::dropIfExists('tl_help_passport_config_passport_config');
    }
};
