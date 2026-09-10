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
        Schema::create('tl_bots_access_settings_access_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('restricted')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fad998aa4c96ef14a6cddfa4');
            $table->index('account_id', 'ix_e94e0b1d43f9a278d1d4fbdf');
        });
        Schema::create('tl_bots_access_settings_access_settings__add_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_bots_access_settings_access_settings', 'id', 'fk_ead67179853c5b73ac4e3131')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_07777149b249dfbe7779');
            $table->index('account_id', 'ix_285fbf6a668b00397fa4399d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_access_settings_access_settings__add_users');
        Schema::dropIfExists('tl_bots_access_settings_access_settings');
    }
};
