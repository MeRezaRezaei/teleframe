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
        Schema::create('tl_j_s_o_n_object_value_json_object_value', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_key')->nullable();
            $table->bigInteger('tl_value')->nullable();
            $table->index('tl_value', 'ix_11ac108b3ed03e699fcbffbf');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_63ff9a1cc3ddd572c847b775');
            $table->index('account_id', 'ix_989be87dbaf6851568a3d6f6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_j_s_o_n_object_value_json_object_value');
    }
};
