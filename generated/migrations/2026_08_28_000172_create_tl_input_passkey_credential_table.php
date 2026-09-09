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
        Schema::create('tl_input_passkey_credential', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_89a9190001a07e46c9714af8');
            $table->index('account_id', 'ix_7bd6f6256bda1d22d70c98d4');
        });
        Schema::create('tl_input_passkey_credential_input_passkey_cre_f2c081028727', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_passkey_credential')->cascadeOnDelete();
            $table->text('pnv_token');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_352eae5736350b0c7b2a602d');
        });
        Schema::create('tl_input_passkey_credential_input_passkey_cre_2ab4607fcbc0', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_passkey_credential')->cascadeOnDelete();
            $table->text('tl_id');
            $table->text('raw_id');
            $table->uuid('response');
            $table->index('response', 'ix_574737ae710e93e62c08f1ef');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c9613e5a54b135cb42909469');
            $table->unique(['account_id', 'tl_id'], 'ux_01e87482b801af2bd41f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_passkey_credential_input_passkey_cre_2ab4607fcbc0');
        Schema::dropIfExists('tl_input_passkey_credential_input_passkey_cre_f2c081028727');
        Schema::dropIfExists('tl_input_passkey_credential');
    }
};
