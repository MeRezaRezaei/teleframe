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
        Schema::create('tl_requirement_to_contact_requirement_to_contact_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1b6300705c2eb8780aac7cb3');
            $table->index('account_id', 'ix_26e611bf0f603e1d4f26d9b0');
        });
        Schema::create('tl_requirement_to_contact_requirement_to_cont_be6f2f636604', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('stars_amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0f271e8409bddef739437f04');
            $table->index('account_id', 'ix_edcb1321cfad3892de0f3d9d');
        });
        Schema::create('tl_requirement_to_contact_requirement_to_contact_premium', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_edec3ae5f2c4b963671aae7f');
            $table->index('account_id', 'ix_93bbc2989192b9fcd7baae83');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_requirement_to_contact_requirement_to_contact_premium');
        Schema::dropIfExists('tl_requirement_to_contact_requirement_to_cont_be6f2f636604');
        Schema::dropIfExists('tl_requirement_to_contact_requirement_to_contact_empty');
    }
};
