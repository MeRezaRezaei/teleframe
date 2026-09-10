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
        Schema::create('tl_account_passkeys_passkeys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e0cebc89e92be34c4491b3a7');
            $table->index('account_id', 'ix_2d57c0848beda1edf678d624');
        });
        Schema::create('tl_account_passkeys_passkeys__passkeys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_account_passkeys_passkeys', 'id', 'fk_9b0f511654639b90d88eca64')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ed93e028556d1de04414');
            $table->index('account_id', 'ix_542ee828cf8d83a8bf47f9fd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_passkeys_passkeys__passkeys');
        Schema::dropIfExists('tl_account_passkeys_passkeys');
    }
};
