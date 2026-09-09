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
        Schema::create('tl_secure_secret_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ab1a69e9d47ab32fe720220d');
            $table->index('account_id', 'ix_0ae1044fb85b13900cae4843');
        });
        Schema::create('tl_secure_secret_settings_secure_secret_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_secret_settings')->cascadeOnDelete();
            $table->uuid('secure_algo');
            $table->index('secure_algo', 'ix_7f38662a7b328e2644329b65');
            $table->binary('secure_secret');
            $table->bigInteger('secure_secret_id');
            $table->index('secure_secret_id', 'ix_19b6c87ed5883011792fdbc3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ad95d089f6b6226b5071cd23');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_secret_settings_secure_secret_settings');
        Schema::dropIfExists('tl_secure_secret_settings');
    }
};
