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
        Schema::create('tl_user_status_user_status_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0e000955581e85574b2ca8d0');
            $table->index('account_id', 'ix_eca268baefbff10a26cb7b65');
        });
        Schema::create('tl_user_status_user_status_last_month', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('by_me')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8ae2ad72164bffc72740366f');
            $table->index('account_id', 'ix_1e7a1be1eca36ed3e618f922');
        });
        Schema::create('tl_user_status_user_status_last_week', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('by_me')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_aa42376ac6399225835699cf');
            $table->index('account_id', 'ix_375d657cca8017d6399aa042');
        });
        Schema::create('tl_user_status_user_status_offline', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('was_online')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_756e567573cc2c4001d583fc');
            $table->index('account_id', 'ix_d65668799d0c16cf04a5e1ec');
        });
        Schema::create('tl_user_status_user_status_online', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('expires')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dd11df9f0b01cb4649d7348f');
            $table->index('account_id', 'ix_2744cad1dd8250c4f236d690');
        });
        Schema::create('tl_user_status_user_status_recently', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('by_me')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_06ba591af8f8b7b7a30c919d');
            $table->index('account_id', 'ix_e7da82874e7a2416b2f614c4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_user_status_user_status_recently');
        Schema::dropIfExists('tl_user_status_user_status_online');
        Schema::dropIfExists('tl_user_status_user_status_offline');
        Schema::dropIfExists('tl_user_status_user_status_last_week');
        Schema::dropIfExists('tl_user_status_user_status_last_month');
        Schema::dropIfExists('tl_user_status_user_status_empty');
    }
};
