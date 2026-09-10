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
        Schema::create('tl_messages_sent_encrypted_message_sent_encrypted_file', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('date')->nullable();
            $table->bigInteger('file')->nullable();
            $table->index('file', 'ix_2912ed65ebf0c9784a577a6d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_55f703335d81dec74e8094dd');
            $table->index('account_id', 'ix_b7bc25ca99b4c9b0729e5031');
        });
        Schema::create('tl_messages_sent_encrypted_message_sent_encrypted_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_598c9320a77c21f8ae93d992');
            $table->index('account_id', 'ix_7764669141a2eb5b8264c138');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_sent_encrypted_message_sent_encrypted_message');
        Schema::dropIfExists('tl_messages_sent_encrypted_message_sent_encrypted_file');
    }
};
