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
        Schema::create('tl_contact', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8738ff5561686a31cdaa0af3');
            $table->index('account_id', 'ix_e9c77894d8a790366ad6c654');
        });
        Schema::create('tl_contact_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_contact')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_e97cd830b985eb33ea5859af');
            $table->uuid('mutual');
            $table->index('mutual', 'ix_0e8a331c353a58bdb27bbc38');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c4f5663e1ce1af19341777d8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_contact_contact');
        Schema::dropIfExists('tl_contact');
    }
};
