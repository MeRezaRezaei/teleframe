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
        Schema::create('tl_secure_value_hash_secure_value_hash', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_f930ae7cf9b8eaf7884e2a32');
            $table->binary('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c54945a870917b48b21ea274');
            $table->index('account_id', 'ix_a471b734916510d27c7e26ba');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_value_hash_secure_value_hash');
    }
};
