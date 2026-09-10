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
        Schema::create('tl_secure_value_error_secure_value_error', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_25871b89a2144632e4ae942d');
            $table->binary('hash')->nullable();
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0f89fa6f3ddb4a130a2c3853');
            $table->index('account_id', 'ix_f7691f439abcf6729e125d36');
        });
        Schema::create('tl_secure_value_error_secure_value_error_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_9fc0bacdaa200dd86fcc58ff');
            $table->binary('data_hash')->nullable();
            $table->text('field')->nullable();
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fb3e56270a3e5e3a2f273686');
            $table->index('account_id', 'ix_9d2516aa9717813410826ac5');
        });
        Schema::create('tl_secure_value_error_secure_value_error_file', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_343e729e976c2d3cfa17a3d8');
            $table->binary('file_hash')->nullable();
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_eb2a6ee428923cbc8b7497ea');
            $table->index('account_id', 'ix_4dfa8c1f772406f5eae515ec');
        });
        Schema::create('tl_secure_value_error_secure_value_error_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_d5acbbb0042df3a97760d0b4');
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_082807367b3cd5616c356328');
            $table->index('account_id', 'ix_e8879e3d880db78a9bc0ff61');
        });
        Schema::create('tl_secure_value_error_secure_value_error_files__file_hash', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_dc33f38f6a1f06638f04a26b')->references('id')->on('tl_secure_value_error_secure_value_error_files')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->binary('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8e467ffc3c4d89ed51ba');
            $table->index('account_id', 'ix_08473081fe2a33b1083b3d13');
        });
        Schema::create('tl_secure_value_error_secure_value_error_front_side', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_e81dcf8143de21b8ab97e7dd');
            $table->binary('file_hash')->nullable();
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_258fc4f8e1f4e886f4e1a65a');
            $table->index('account_id', 'ix_3b6ebd26ae27f1ed73657a67');
        });
        Schema::create('tl_secure_value_error_secure_value_error_reverse_side', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_8f2116d99e045ae570a08766');
            $table->binary('file_hash')->nullable();
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c9c40dfd856ae5b9ab0706d5');
            $table->index('account_id', 'ix_3cb0ac9f88cdc424c9f73f9f');
        });
        Schema::create('tl_secure_value_error_secure_value_error_selfie', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_0df31295a21aad63f986ae61');
            $table->binary('file_hash')->nullable();
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6d756f2df9671448a507070b');
            $table->index('account_id', 'ix_09027b57d017785bd9fac916');
        });
        Schema::create('tl_secure_value_error_secure_value_error_translation_file', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_2c43227f1f8d5eb1a6799d82');
            $table->binary('file_hash')->nullable();
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_63511569ab834c67bf86a61d');
            $table->index('account_id', 'ix_21857b0a2b67f00809087cd6');
        });
        Schema::create('tl_secure_value_error_secure_value_error_translation_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_fbec3e4615e9ac3c79ed2ecc');
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_20890f5818f4fda5b866c2ec');
            $table->index('account_id', 'ix_991b5765abef00e2b5dd8dea');
        });
        Schema::create('tl_secure_value_error_secure_value_error_tran_f5846064b312', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_71f39bbbd3f7ebdaa027c673')->references('id')->on('tl_secure_value_error_secure_value_error_translation_files')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->binary('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_487d4b242ffeb4d6c379');
            $table->index('account_id', 'ix_04792f1e491340016f9ff066');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_tran_f5846064b312');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_translation_files');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_translation_file');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_selfie');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_reverse_side');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_front_side');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_files__file_hash');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_files');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_file');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error_data');
        Schema::dropIfExists('tl_secure_value_error_secure_value_error');
    }
};
