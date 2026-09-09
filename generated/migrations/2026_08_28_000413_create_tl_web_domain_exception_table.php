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
        Schema::create('tl_web_domain_exception', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c93d81fb627a01f1a39ba931');
            $table->index('account_id', 'ix_8c96863a9273ed62fd4b8a67');
        });
        Schema::create('tl_web_domain_exception_web_domain_exception', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_web_domain_exception')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('domain');
            $table->text('url');
            $table->text('title');
            $table->bigInteger('favicon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cde59f344cc1d08339858f4f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_web_domain_exception_web_domain_exception');
        Schema::dropIfExists('tl_web_domain_exception');
    }
};
