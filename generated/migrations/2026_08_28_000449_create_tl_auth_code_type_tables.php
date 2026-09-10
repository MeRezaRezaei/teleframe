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
        Schema::create('tl_auth_code_type_code_type_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3656124a117b339a63b73ad4');
            $table->index('account_id', 'ix_f100afba528f10a9d30dc559');
        });
        Schema::create('tl_auth_code_type_code_type_flash_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a8b51056054b671ba6a5b05d');
            $table->index('account_id', 'ix_83dd3877ad1d310771750bf9');
        });
        Schema::create('tl_auth_code_type_code_type_fragment_sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ac250dd45906942c91b13d4b');
            $table->index('account_id', 'ix_f70c5dd74eee95fab5efb7ea');
        });
        Schema::create('tl_auth_code_type_code_type_missed_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d0005b5a7c1eebf49a43d447');
            $table->index('account_id', 'ix_fb71745d54c6e935a33a1310');
        });
        Schema::create('tl_auth_code_type_code_type_sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_60f7c531a6e483274171242e');
            $table->index('account_id', 'ix_365c8e94746f97e4c00df79f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_code_type_code_type_sms');
        Schema::dropIfExists('tl_auth_code_type_code_type_missed_call');
        Schema::dropIfExists('tl_auth_code_type_code_type_fragment_sms');
        Schema::dropIfExists('tl_auth_code_type_code_type_flash_call');
        Schema::dropIfExists('tl_auth_code_type_code_type_call');
    }
};
