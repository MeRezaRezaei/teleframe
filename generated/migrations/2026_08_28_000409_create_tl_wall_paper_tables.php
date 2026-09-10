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
        Schema::create('tl_wall_paper_wall_paper', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->boolean('tl_default')->default(false);
            $table->boolean('pattern')->default(false);
            $table->boolean('dark')->default(false);
            $table->bigInteger('access_hash')->nullable();
            $table->text('slug')->nullable();
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_3aad211dc24c6292bcbdcf24');
            $table->bigInteger('settings')->nullable();
            $table->index('settings', 'ix_7e121aca7de78d4b2ee72c64');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_744c8c09459472573970a07a');
            $table->index('account_id', 'ix_5d4ae2c6fa3db3d823b50b9c');
        });
        Schema::create('tl_wall_paper_wall_paper_no_file', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('flags')->nullable();
            $table->boolean('tl_default')->default(false);
            $table->boolean('dark')->default(false);
            $table->bigInteger('settings')->nullable();
            $table->index('settings', 'ix_07305e44a2ea75271a5da1e3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ad32144ba13275af2bb30f60');
            $table->index('account_id', 'ix_e094c0391455605ea852d35d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_wall_paper_wall_paper_no_file');
        Schema::dropIfExists('tl_wall_paper_wall_paper');
    }
};
