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
        Schema::create('tl_saved_contact_saved_phone_contact', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('phone')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_58c2fc2da9159939a5b23fb4');
            $table->index('account_id', 'ix_4dcc1e94d170a91d7f3f0912');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_saved_contact_saved_phone_contact');
    }
};
