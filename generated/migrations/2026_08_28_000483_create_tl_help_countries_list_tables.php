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
        Schema::create('tl_help_countries_list_countries_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8256f3bd1f1743dac0f16c4f');
            $table->index('account_id', 'ix_27021fb7835e4deda247e769');
        });
        Schema::create('tl_help_countries_list_countries_list__countries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_help_countries_list_countries_list', 'id', 'fk_f5d0dbd28c603184107b88cd')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e6a2b6398709298542b2');
            $table->index('account_id', 'ix_537c9dda353368abdc1e2f47');
        });
        Schema::create('tl_help_countries_list_countries_list_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_78c9f51f23d1e8ddaf674930');
            $table->index('account_id', 'ix_a29065b24fa34f499520265f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_help_countries_list_countries_list_not_modified');
        Schema::dropIfExists('tl_help_countries_list_countries_list__countries');
        Schema::dropIfExists('tl_help_countries_list_countries_list');
    }
};
