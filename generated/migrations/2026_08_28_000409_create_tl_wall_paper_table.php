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
        Schema::create('tl_wall_paper', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c4bc23ff8c4692f00239c387');
            $table->index('account_id', 'ix_2603decd7d17e3dafa623364');
        });
        Schema::create('tl_wall_paper_wall_paper', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_wall_paper')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('flags')->nullable();
            $table->boolean('creator')->default(false);
            $table->boolean('tl_default')->default(false);
            $table->boolean('pattern')->default(false);
            $table->boolean('dark')->default(false);
            $table->bigInteger('access_hash');
            $table->text('slug');
            $table->uuid('document');
            $table->index('document', 'ix_3aad211dc24c6292bcbdcf24');
            $table->uuid('settings')->nullable();
            $table->index('settings', 'ix_7e121aca7de78d4b2ee72c64');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5d4ae2c6fa3db3d823b50b9c');
            $table->unique(['account_id', 'tl_id'], 'ux_8e2f4cb3a80635772b6d');
        });
        Schema::create('tl_wall_paper_wall_paper_no_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_wall_paper')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('flags')->nullable();
            $table->boolean('tl_default')->default(false);
            $table->boolean('dark')->default(false);
            $table->uuid('settings')->nullable();
            $table->index('settings', 'ix_07305e44a2ea75271a5da1e3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e094c0391455605ea852d35d');
            $table->unique(['account_id', 'tl_id'], 'ux_9a18e20aa0c22080615f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_wall_paper_wall_paper_no_file');
        Schema::dropIfExists('tl_wall_paper_wall_paper');
        Schema::dropIfExists('tl_wall_paper');
    }
};
