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
        Schema::create('tl_messages_saved_gifs_saved_gifs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7b514d443e04512edc3d1558');
            $table->index('account_id', 'ix_d077cff3ae2163fcdfd4e2f1');
        });
        Schema::create('tl_messages_saved_gifs_saved_gifs__gifs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_3e975f25bc6f49abdf5edda7')->references('id')->on('tl_messages_saved_gifs_saved_gifs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9be9d5773b7ba95d6909');
            $table->index('account_id', 'ix_246526dd4fa0048442b2d798');
        });
        Schema::create('tl_messages_saved_gifs_saved_gifs_not_modified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b49061971d4bb9fb2c8d310a');
            $table->index('account_id', 'ix_c36d8da49dc68af7f6836f1c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_saved_gifs_saved_gifs_not_modified');
        Schema::dropIfExists('tl_messages_saved_gifs_saved_gifs__gifs');
        Schema::dropIfExists('tl_messages_saved_gifs_saved_gifs');
    }
};
