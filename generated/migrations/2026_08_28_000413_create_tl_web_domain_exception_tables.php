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
        Schema::create('tl_web_domain_exception_web_domain_exception', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('domain')->nullable();
            $table->text('url')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('favicon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_aeb96e32a662c54743939ecb');
            $table->index('account_id', 'ix_cde59f344cc1d08339858f4f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_domain_exception_web_domain_exception');
    }
};
