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
        Schema::create('tl_exported_contact_token_exported_contact_token', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->integer('expires')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b945ffebc582af10cd21cb08');
            $table->index('account_id', 'ix_3128b40bbba33d2e96ada189');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_exported_contact_token_exported_contact_token');
    }
};
