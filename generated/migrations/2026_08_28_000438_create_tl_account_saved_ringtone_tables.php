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
        Schema::create('tl_account_saved_ringtone_saved_ringtone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_19233042f4c8edc9623cf18f');
            $table->index('account_id', 'ix_d8d71019ef1acc49c0e8b31e');
        });
        Schema::create('tl_account_saved_ringtone_saved_ringtone_converted', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_78f19e7c001f061b7caac04c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1d140e45f145a4b9b1fd9cd6');
            $table->index('account_id', 'ix_8240b06ae49953d14cd726db');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_saved_ringtone_saved_ringtone_converted');
        Schema::dropIfExists('tl_account_saved_ringtone_saved_ringtone');
    }
};
